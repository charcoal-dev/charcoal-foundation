<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Traits;

use App\Shared\Enums\Interfaces;
use Charcoal\Http\Commons\Enums\HeaderKeyValidation;
use Charcoal\Http\Commons\Enums\ParamKeyValidation;
use Charcoal\Http\Server\Enums\ForwardingMode;
use Charcoal\Http\TrustProxy\Config\TrustedProxy;

/**
 * Provides functionality to configure HTTP interfaces using a given configuration array.
 * This trait defines methods for processing a configuration array and setting up HTTP interfaces
 * with appropriate options. It supports features such as virtual host setup, CORS policy configuration,
 * trusted proxy management, router triggers, and per-request security constraints.
 */
trait SapiConfigBuilderTrait
{
    /**
     * Configures HTTP interfaces based on the provided configuration data.
     */
    public function httpInterfacesFromFileConfig(mixed $config): void
    {
        if (!is_array($config) || !$config) {
            return;
        }

        foreach ($config as $name => $server) {
            $interface = Interfaces::tryFrom(strval($name));
            if (!$interface) {
                throw new \OutOfBoundsException("No matching interface found between Enum and config");
            }

            $httpSapi = $this->sapi->http($interface);

            // Virtual Hosts
            $hosts = $server["hosts"] ?? null;
            if (is_array($hosts) && count($hosts)) {
                for ($i = 0; $i < count($hosts); $i++) {
                    if (isset($hosts[$i]["hostname"])) {
                        $port = $hosts[$i]["port"] ?? null;
                        if (!is_int($port) || $port < 0 || $port > 65535) {
                            throw new \InvalidArgumentException(
                                "Invalid port configuration for virtual host at index: " . $i);
                        }

                        $tls = $hosts[$i]["tls"] ?? null;
                        if (!is_bool($tls)) {
                            throw new \InvalidArgumentException(
                                "Invalid TLS configuration for virtual host at index: " . $i);
                        }

                        $dnat = $hosts[$i]["dnat"] ?? null;
                        if (!is_bool($dnat)) {
                            throw new \InvalidArgumentException(
                                "Invalid DNAT configuration for virtual host at index: " . $i);
                        }

                        $allowInternal = $hosts[$i]["allowInternalConnections"] ?? false;
                        if (!is_bool($allowInternal)) {
                            throw new \InvalidArgumentException(
                                "Invalid value for \"allowInternalConnections\" for virtual host at index: " . $i);
                        }

                        $httpSapi->addServer($hosts[$i]["hostname"], $port, $tls,
                            $dnat ? ForwardingMode::DNAT : ForwardingMode::ReverseProxy, $allowInternal);
                    }
                }
            }

            if (!$hosts) {
                throw new \InvalidArgumentException("Invalid hosts configuration for interface: " . $interface->name);
            }

            // Cors
            if (!is_array($server["cors"] ?? null)) {
                throw new \InvalidArgumentException(
                    "Invalid cors configuration for interface: " . $interface->name);
            }

            $corsEnabled = $server["cors"]["enforced"] ?? null;
            if (!is_bool($corsEnabled)) {
                throw new \InvalidArgumentException("CORS enforced should be a boolean true/false");
            }

            $corsMaxAge = $server["cors"]["maxAge"] ?? null;
            if (!is_int($corsMaxAge) || $corsMaxAge < 0) {
                throw new \InvalidArgumentException("CORS max age should be an integer");
            }

            $corsOrigins = $server["cors"]["origins"] ?? null;
            if (!is_array($corsOrigins) || !$corsOrigins) {
                throw new \InvalidArgumentException("CORS origins should be an array of strings");
            }

            foreach ($corsOrigins as $origin) {
                if (!is_string($origin) || !$origin) {
                    throw new \InvalidArgumentException("CORS origin should be a string");
                }
            }

            $httpSapi->corsPolicy($corsEnabled, $corsMaxAge);
            foreach ($corsOrigins as $corsOrigin) {
                $httpSapi->corsAllowOrigin($corsOrigin);
            }

            // Trust Proxy
            $trustProxies = $server["proxies"] ?? [];
            if (!is_array($trustProxies)) {
                throw new \InvalidArgumentException("Invalid proxies configuration for interface: " . $interface->name);
            }

            foreach ($trustProxies as $trustProxy) {
                // CIDR
                $cidr = $trustProxy["cidr"] ?? null;
                if (!is_array($cidr) || !$cidr) {
                    throw new \InvalidArgumentException("Invalid CIDR configuration for interface: " .
                        $interface->name);
                }

                foreach ($cidr as $ipRange) {
                    if (!is_string($ipRange) || !$ipRange) {
                        throw new \InvalidArgumentException("Invalid CIDR configuration for interface: " .
                            $interface->name);
                    }
                }

                // Triggers
                $useForwarded = $trustProxy["useForwarded"] ?? null;
                if (!is_bool($useForwarded)) {
                    throw new \InvalidArgumentException("Invalid \"useForwarded\" configuration for interface: " .
                        $interface->name);
                }

                $protoFromTrustedEdge = $trustProxy["protoFromTrustedEdge"] ?? null;
                if (!is_bool($trustProxy["protoFromTrustedEdge"])) {
                    throw new \InvalidArgumentException("Invalid protoFromTrustedEdge configuration for interface: " .
                        $interface->name);
                }

                $maxHops = $trustProxy["maxHops"] ?? null;
                if (!is_int($maxHops) || $maxHops < 0 || $maxHops > 30) {
                    throw new \InvalidArgumentException("Invalid maxHops configuration for interface: " .
                        $interface->name);
                }

                $httpSapi->addTrustedProxy(new TrustedProxy($useForwarded, $cidr, $maxHops, $protoFromTrustedEdge));
            }

            // Triggers
            $wwwSupport = $server["wwwSupport"] ?? null;
            if (!is_bool($wwwSupport)) {
                throw new \InvalidArgumentException("Invalid wwwSupport configuration for interface: " .
                    $interface->name);
            }

            $enforceTls = $server["enforceTls"] ?? null;
            if (!is_bool($enforceTls)) {
                throw new \InvalidArgumentException("Invalid enforceTls configuration for interface: " .
                    $interface->name);
            }

            $httpSapi->routerConfig(enforceTls: $enforceTls, wwwSupport: $wwwSupport);

            $constraints = $server["perRequestConstraints"] ?? null;
            if (!is_array($constraints) || !$constraints) {
                throw new \InvalidArgumentException("Invalid per-request constraints configuration for interface: " .
                    $interface->name);
            }

            // Security
            $httpSapi->perRequestConstraints(
                maxUriBytes: (int)($constraints["maxUriBytes"] ?? -1),
                maxHeaders: (int)($constraints["maxHeaders"] ?? -1),
                maxHeaderLength: (int)($constraints["maxHeaderLength"] ?? -1),
                headerKeyValidation: HeaderKeyValidation::tryFrom(strval($constraints["headerKeyValidation"] ?? "")) ??
                HeaderKeyValidation::RFC7230,
                paramKeyValidation: ParamKeyValidation::tryFrom(strval($constraints["paramKeyValidation"] ?? "")) ??
                ParamKeyValidation::STRICT,
                maxBodyBytes: (int)($constraints["maxBodyBytes"] ?? -1),
                maxParams: (int)($constraints["maxParams"] ?? -1),
                maxParamLength: (int)($constraints["maxParamLength"] ?? -1),
                dtoMaxDepth: (int)($constraints["dtoMaxDepth"] ?? -1),
            );
        }
    }
}