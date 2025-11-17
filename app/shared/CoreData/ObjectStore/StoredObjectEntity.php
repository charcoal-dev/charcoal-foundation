<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\ObjectStore;

use App\Shared\CoreData\Contracts\StorableObjectInterface;
use App\Shared\CoreData\Internal\CoreDataConstants;
use Charcoal\App\Kernel\Clock\Clock;
use Charcoal\App\Kernel\Contracts\Orm\Entity\CacheableEntityInterface;
use Charcoal\App\Kernel\Contracts\Orm\Entity\StorageHooksInterface;
use Charcoal\App\Kernel\Orm\Entity\CacheableEntityTrait;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Cipher\Cipher;
use Charcoal\Cipher\Encrypted\EncryptedObject;
use Charcoal\Contracts\Security\Secrets\SecretKeyInterface;
use Charcoal\Contracts\Storage\Enums\FetchOrigin;

/**
 * Class StoredObjectEntity
 * @package App\Shared\CoreData\ObjectStore
 */
final class StoredObjectEntity extends OrmEntityBase implements
    CacheableEntityInterface,
    StorageHooksInterface
{
    use CacheableEntityTrait;

    public string $ref;
    public int $version;
    public string $payload;
    public ?string $kid;
    public int $updatedOn;

    private readonly StorableObjectInterface $recoveredObject;


    /**
     * Generates a unique reference ID by combining the reference and version.
     */
    public static function uniqueRefId(string $ref, int $version = 1): string
    {
        return sprintf("%s:%05d", $ref, $version);
    }

    /**
     * Prepares the object for storage after secure encryption.
     * @throws WrappedException
     * @api
     */
    public static function encryptedEnvelope(
        SecretKeyInterface      $secret,
        string                  $ref,
        StorableObjectInterface $object,
        int                     $version = 1,
        Cipher                  $cipher = Cipher::AES_256_GCM,
    ): self
    {
        $storedObject = new self();
        $storedObject->ref = $ref;
        $storedObject->version = $version;
        $storedObject->kid = $secret->ref();
        $storedObject->updatedOn = Clock::getTimestamp();
        self::validateFields($storedObject);

        try {
            $encrypted = $cipher->encrypt($secret, $object, $ref, $version);
            $storedObject->payload = chr(strlen($encrypted->iv()))
                . chr(strlen($encrypted->tag()))
                . $encrypted->iv()
                . $encrypted->tag()
                . $encrypted->ciphertext();
        } catch (\Throwable $t) {
            throw new WrappedException($t, "Failed to encrypt object for storage");
        }

        return $storedObject;
    }

    /**
     * Prepares the object for storage without encryption.
     * @throws WrappedException
     * @api
     */
    public static function plainEnvelope(
        string                  $ref,
        StorableObjectInterface $object,
        int                     $version = 1,
        bool                    $clone = true,
    ): self
    {
        $storedObject = new self();
        $storedObject->ref = $ref;
        $storedObject->version = $version;
        $storedObject->kid = null;
        $storedObject->updatedOn = Clock::getTimestamp();
        self::validateFields($storedObject);

        try {
            $storedObject->payload = serialize($clone ? clone $object : $object);
        } catch (\Throwable $t) {
            throw new WrappedException($t, "Failed to encrypt object for storage");
        }

        return $storedObject;
    }

    /**
     * Validates the stored object properties; throws an exception if invalid.
     */
    protected static function validateFields(self $storedObject): void
    {
        if (!$storedObject->ref || !preg_match(CoreDataConstants::STORED_OBJECT_REF_REGEXP, $storedObject->ref)) {
            throw new \InvalidArgumentException("Invalid stored object reference: " . $storedObject->ref);
        }

        if (!$storedObject->version || $storedObject->version < 1 || $storedObject->version > 65535) {
            throw new \OutOfRangeException("Invalid stored object version: " . $storedObject->version);
        }
    }

    /**
     * @return string
     */
    public function getPrimaryId(): string
    {
        return self::uniqueRefId($this->ref, $this->version);
    }

    /**
     * @param array|null $unserializeAllowedFqcn
     * @param SecretKeyInterface|null $secret
     * @param Cipher $cipher
     * @return StorableObjectInterface
     * @throws WrappedException
     */
    public function getObject(
        ?array              $unserializeAllowedFqcn = null,
        ?SecretKeyInterface $secret = null,
        Cipher              $cipher = Cipher::AES_256_GCM
    ): StorableObjectInterface
    {
        if (isset($this->recoveredObject)) {
            return $this->recoveredObject;
        }

        // Encrypted Objects:
        if (isset($this->kid)) {
            if (!$secret) {
                throw new \InvalidArgumentException("Secret key is required to decrypt encrypted object");
            }

            $ciphertext = $this->payload;
            if (!$ciphertext || strlen($ciphertext) < 2) {
                throw new \UnexpectedValueException("Invalid encrypted blob: " . $this->ref, 1000);
            }

            $ivLength = ord(substr($ciphertext, 0, 1));
            $tagLength = ord(substr($ciphertext, 1, 1));
            $ciphertext = substr($ciphertext, 2);
            if (!$ivLength < 0 || strlen($ciphertext) < $ivLength + $tagLength) {
                throw new \UnexpectedValueException("Invalid encrypted blob: " . $this->ref, 1001);
            }

            $ivBytes = substr($ciphertext, 0, $ivLength);
            $ciphertext = substr($ciphertext, $ivLength);
            $tagBytes = $tagLength > 0 ? substr($ciphertext, 0, $tagLength) : null;
            if ($tagLength > 0) {
                $ciphertext = substr($ciphertext, $tagLength);
            }

            try {
                $decrypted = $cipher->decrypt(
                    $secret,
                    new EncryptedObject($cipher, $ciphertext, $ivBytes, $tagBytes),
                    unserializeAllowedFqcn: $unserializeAllowedFqcn
                );

                if (!$decrypted instanceof StorableObjectInterface) {
                    throw new \UnexpectedValueException("Invalid decrypted object: " . $this->ref, 2001);
                }

                return $this->recoveredObject = $decrypted;
            } catch (\Throwable $t) {
                throw new WrappedException($t, "Failed to decrypt object: " . $this->ref);
            }
        }

        // Plain Object (Unencrypted)
        $options = null;
        if ($unserializeAllowedFqcn) {
            $options = ["allowed_classes" => $unserializeAllowedFqcn];
        }

        try {
            $unserialized = unserialize($this->payload, $options);
            if (!$unserialized instanceof StorableObjectInterface) {
                throw new \UnexpectedValueException("Invalid unserialized object: " . $this->ref, 2002);
            }
        } catch (\Throwable $t) {
            throw new WrappedException($t, "Failed to unserialize object: " . $this->ref);
        }

        return $this->recoveredObject = $unserialized;
    }

    /**
     * @return StorableObjectInterface|null
     * @api
     */
    public function getRecoveredObject(): ?StorableObjectInterface
    {
        return $this->recoveredObject ?? null;
    }

    /**
     * @param FetchOrigin $origin
     * @return string|null
     */
    public function onRetrieve(FetchOrigin $origin): ?string
    {
        return sprintf('Stored object "%s" (v: %d) retrieved from: %s',
            $this->ref, $this->version, $origin->name);
    }

    /**
     * @return string|null
     */
    public function onCacheStore(): ?string
    {
        return sprintf('Stored object "%s" (v: %d) stored in CACHE',
            $this->ref, $this->version);
    }
}