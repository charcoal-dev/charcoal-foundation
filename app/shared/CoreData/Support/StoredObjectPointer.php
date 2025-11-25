<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Support;

use App\Shared\Contracts\PersistedConfigObjectInterface;
use App\Shared\CoreData\Internal\CoreDataConstants;
use App\Shared\CoreData\ObjectStore\StoredObjectEntity;

/**
 * This class provides a way to uniquely identify and access persisted configuration objects.
 * Represents a reference to a stored object, consisting of its fully qualified class name,
 * a unique reference identifier, and a version number.
 */
final readonly class StoredObjectPointer
{
    public string $storageId;

    /**
     * @param class-string<PersistedConfigObjectInterface> $fqcn
     * @param string $ref
     * @param int $version
     * @param bool $validate
     */
    public function __construct(
        public string $fqcn,
        public string $ref,
        public int    $version,
        bool          $validate = true
    )
    {
        if ($validate) {
            if (!$this->ref || !preg_match(CoreDataConstants::STORED_OBJECT_REF_REGEXP, $this->ref)) {
                throw new \InvalidArgumentException("Invalid stored object reference: " . $this->ref);
            }

            if (!$this->version || $this->version < 1 || $this->version > 65535) {
                throw new \OutOfRangeException("Invalid stored object version: " . $this->version);
            }
        }

        $this->storageId = "objectStore:" . StoredObjectEntity::uniqueRefId($this->ref, $this->version);
    }
}