<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use App\Shared\AppConstants;
use Charcoal\App\Kernel\Contracts\Enums\SecretKeysEnumInterface;
use Charcoal\App\Kernel\Security\Secrets\SecretEntropyRemixing;
use Charcoal\Security\Secrets\Support\SecretKeyRef;

/**
 * Class SecretKeys
 * @package App\Shared\Enums
 */
enum SecretKeys: string implements SecretKeysEnumInterface
{
    case Primary = "charcoal_app_primary";
    case CoreDataModule = "coreData";
    case Telemetry = "telemetry";

    /**
     * @return int
     */
    public function getCurrentVersion(): int
    {
        return 1;
    }

    /**
     * @return SecretKeyRef
     */
    public function getKeyRef(): SecretKeyRef
    {
        $remixing = $this->getRemixAttributes();
        return new SecretKeyRef(
            false,
            $this->getRef(),
            $this->getCurrentVersion(),
            $this->getNamespace(),
            $remixing?->message,
            $remixing?->iterations,
        );
    }

    /**
     * @return SecretEntropyRemixing|null
     */
    public function getRemixAttributes(): ?SecretEntropyRemixing
    {
        return match ($this) {
            self::Telemetry,
            self::CoreDataModule => new SecretEntropyRemixing($this->value,
                AppConstants::SECRETS_REMIX_ITERATIONS),
            default => null
        };
    }

    /**
     * Remixed keys must use ref of the parent key.
     * @return string
     */
    public function getRef(): string
    {
        return match ($this) {
            self::Primary,
            self::Telemetry,
            self::CoreDataModule => self::Primary->value,
        };
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return AppConstants::SECRETS_LOCAL_NAMESPACE;
    }

    /**
     * @return SecretsStores
     */
    public function getStore(): SecretsStores
    {
        return SecretsStores::Local;
    }
}