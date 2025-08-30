<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Builder\Traits;

use App\Shared\Enums\Interfaces;
use Charcoal\Http\Commons\Enums\HeaderKeyValidation;
use Charcoal\Http\Commons\Enums\ParamKeyValidation;
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
     * The configuration data for setting up HTTP interfaces.
     * Expected to be an array containing HTTP interface definitions,
     * their configurations for virtual hosts, CORS policies, trusted proxies,
     * router triggers, and per-request constraints.
     * If the input is not a valid array or empty, the method returns early without any effect.
     * Each interface configuration in the array may include:
     * - "listen" for virtual hosts
     * - "cors" for CORS policy settings
     * - "proxies" for trusted proxy definitions
     * - "wwwSupport" and "enforceTls" for router triggers
     * - Per-request constraints such as URI, headers, and body sizes.
     */
    public function httpInterfacesFromFileConfig(mixed $config): void
    {
        if (!is_array($config) || !$config) {
            return;
        }

        foreach ($config as $name => $server) {
            $interface = Interfaces::tryFrom(strval($name));
            if (!$interface) {
                continue;
            }

            $httpSapi = $this->sapi->http($interface);

            // Virtual Hosts
            $listen = $server["listen"] ?? null;
            if (is_array($listen) && count($listen)) {
                for ($i = 0; $i < count($listen); $i++) {
                    if (isset($virtualHost[$i]["hostname"])) {
                        $ports = $virtualHost[$i]["ports"] ?? null;
                        if (!is_null($ports) && !is_array($ports)) {
                            throw new \InvalidArgumentException(
                                "Invalid ports configuration for virtual host at index: " . $i);
                        }

                        if (is_array($ports)) {
                            foreach ($ports as $port) {
                                if (!is_int($port) || $port < 0 || $port > 65535) {
                                    throw new \InvalidArgumentException(
                                        "Invalid port configuration for virtual host at index: " . $i);
                                }
                            }
                        }

                        $httpSapi->addServer($virtualHost[$i]["hostname"], ...($ports ?: [80]));
                    }
                }
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
            $trustProxies = $server["proxies"] ?? null;
            if (!is_array($trustProxies) || !$trustProxies) {
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
                $xff = $trustProxy["xff"] ?? null;
                if (!is_bool($xff)) {
                    throw new \InvalidArgumentException("Invalid XFF configuration for interface: " .
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

                $httpSapi->addTrustedProxy(new TrustedProxy($xff, $cidr, $maxHops, $protoFromTrustedEdge));
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

            // Security
            $httpSapi->perRequestConstraints(
                maxUriBytes: (int)($server["maxUriBytes"] ?? -1),
                maxHeaders: (int)($server["maxHeaders"] ?? -1),
                maxHeaderLength: (int)($server["maxHeaderLength"] ?? -1),
                headerKeyValidation: match ((string)$server["headerKeyValidation"] ?? "") {
                    "rfc7230" => HeaderKeyValidation::RFC7230,
                    "strict" => HeaderKeyValidation::STRICT,
                    default => throw new \InvalidArgumentException("Invalid header key validation for interface: " .
                        $interface->name),
                },
                paramKeyValidation: match ((string)$server["paramKeyValidation"] ?? "") {
                    "regular" => ParamKeyValidation::REGULAR,
                    "strict" => HeaderKeyValidation::STRICT,
                    default => throw new \InvalidArgumentException("Invalid param key validation for interface: " .
                        $interface->name)
                },
                maxBodyBytes: (int)($server["maxBodyBytes"] ?? -1),
                maxParams: (int)($server["maxParams"] ?? -1),
                maxParamLength: (int)($server["maxParamLength"] ?? -1),
                dtoMaxDepth: (int)($server["dtoMaxDepth"] ?? -1),
            );
        }
    }
}