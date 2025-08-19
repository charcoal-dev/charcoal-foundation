<?php
declare(strict_types=1);

namespace App\Shared\Core\Config;

use App\Shared\Context\CipherKey;
use App\Shared\Utility\StringHelper;
use Charcoal\Buffers\Frames\Bytes32;
use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Cipher\CipherMode;

/**
 * Class CiphersConfig
 * @package App\Shared\Core\Config
 */
readonly class CiphersConfig
{
    public array $keychain;

    use NoDumpTrait;
    use NotCloneableTrait;

    /**
     * @param mixed $configData
     */
    public function __construct(mixed $configData)
    {
        if (!is_array($configData) && !is_null($configData)) {
            throw new \UnexpectedValueException("Ciphers configuration is required");
        }

        $keychain = [];
        if ($configData) {
            $keychainData = $configData["keychain"] ?? null;
            if (!is_array($keychainData) && !is_null($keychainData)) {
                throw new \UnexpectedValueException("Ciphers keychain configuration is required");
            }

            if ($keychainData) {
                foreach ($keychainData as $keyId => $cipherObj) {
                    $cipherEnum = CipherKey::tryFrom(strval($keyId));
                    if (!$cipherEnum) {
                        throw new \DomainException("No such cipher enum declared in " . CipherKey::class);
                    }

                    $entropy = StringHelper::getTrimmedOrNull($cipherObj["entropy"] ?? null);
                    if (!is_string($entropy) || !preg_match('/^(0x)?[a-f0-9]{64}$/i', $entropy)) {
                        throw new \UnexpectedValueException("Invalid cipher entropy for: " . $cipherEnum->name);
                    }

                    $entropy = Bytes32::fromBase16($entropy);
                    if (strlen(trim($entropy->raw())) !== 32) {
                        throw new \DomainException("Insecure cipher entropy for: " . $cipherEnum->name);
                    }

                    $mode = StringHelper::getTrimmedOrNull($cipherObj["mode"] ?? null);
                    if (!$mode || !in_array(strtoupper($mode), ["CBC", "GCM"])) {
                        throw new \UnexpectedValueException("Invalid cipher mode for: " . $cipherEnum->name);
                    }

                    $mode = match (strtoupper($mode)) {
                        "GCM" => CipherMode::GCM,
                        default => CipherMode::CBC,
                    };

                    $keychain[$cipherEnum->value] = ["entropy" => $entropy, "mode" => $mode];
                }
            }
        }

        $this->keychain = $keychain;
    }
}