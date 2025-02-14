<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Auth\Sessions;

use App\Shared\AppDbTables;
use App\Shared\Contracts\Accounts\AccountRealm;
use App\Shared\Contracts\Accounts\RealmAccountInterface;
use App\Shared\Foundation\Auth\AuthModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Exception\NoChangesException;
use Charcoal\App\Kernel\Orm\Repository\ChecksumAwareRepositoryTrait;
use Charcoal\App\Kernel\Orm\Repository\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\EntityUpdatableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\Frames\Bytes16;
use Charcoal\Buffers\Frames\Bytes20;
use Charcoal\Buffers\Frames\Bytes20P;
use Charcoal\Buffers\Frames\Bytes32;
use Charcoal\Cache\Exception\CacheException;
use Charcoal\Cipher\CipherMethod;
use Charcoal\Database\Queries\SortFlag;
use Charcoal\OOP\Vectors\StringVector;

/**
 * Class SessionsHandler
 * @package App\Shared\Foundation\Auth\Sessions
 * @property AuthModule $module
 */
class SessionsHandler extends AbstractOrmRepository
{
    use ChecksumAwareRepositoryTrait;
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    /**
     * @param AuthModule $module
     */
    public function __construct(AuthModule $module)
    {
        parent::__construct($module, AppDbTables::AUTH_SESSIONS);
    }

    /**
     * @param SessionEntity $session
     * @return Bytes20
     * @throws \Charcoal\App\Kernel\Entity\Exception\ChecksumComputeException
     */
    public function calculateChecksum(SessionEntity $session): Bytes20
    {
        return $this->entityChecksumCalculate($session);
    }

    /**
     * @param SessionEntity $session
     * @return void
     * @throws \Charcoal\App\Kernel\Entity\Exception\ChecksumComputeException
     * @throws \Charcoal\App\Kernel\Entity\Exception\ChecksumMismatchException
     */
    public function validateChecksum(SessionEntity $session): void
    {
        $this->entityChecksumValidate($session);
    }

    /**
     * @param Bytes16 $hmacSecret
     * @return Buffer
     * @throws \Charcoal\Cipher\Exception\CipherException
     */
    public function encryptHmacSecret(Bytes16 $hmacSecret): Buffer
    {
        $encrypted = $this->module->getCipher($this)->encryptSerialize($hmacSecret->raw(), CipherMethod::GCM, plainString: true);
        if ($encrypted->len() !== 48) {
            throw new \UnexpectedValueException('Expected 48 bytes for Hmac secret encrypted with GCM, got ' . $encrypted->len());
        }

        return $encrypted->readOnly();
    }

    /**
     * @param \Charcoal\Buffers\Buffer $encrypted
     * @return \Charcoal\Buffers\Frames\Bytes16
     * @throws \Charcoal\Cipher\Exception\CipherException
     */
    public function decryptHmacSecret(Buffer $encrypted): Bytes16
    {
        $decrypt = $this->module->getCipher($this)->decryptSerialized($encrypted, CipherMethod::GCM, plainString: true);
        if (!is_string($decrypt)) {
            throw new \UnexpectedValueException(sprintf('Unexpected value of type "%s" after decrypting Hmac secret', gettype($decrypt)));
        } elseif (strlen($decrypt) !== 16) {
            throw new \LengthException('Expected Hmac secret of 16 bytes, got ' . strlen($decrypt));
        }

        return new Bytes16($decrypt);
    }

    /**
     * @param RealmAccountInterface $account
     * @param SessionType $type
     * @param Bytes32 $deviceFp
     * @param Bytes16 $hmacSecret
     * @param string $ipAddress
     * @param string $userAgent
     * @return SessionEntity
     * @throws \Charcoal\App\Kernel\Entity\Exception\ChecksumComputeException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Charcoal\Cipher\Exception\CipherException
     */
    public function createSession(
        RealmAccountInterface $account,
        SessionType           $type,
        Bytes32               $deviceFp,
        Bytes16               $hmacSecret,
        string                $ipAddress,
        string                $userAgent,
    ): SessionEntity
    {
        $session = new SessionEntity();
        $session->checksum = new Bytes20P("tba");
        $session->realm = $account->getRealm();
        $session->type = $type;
        $session->archived = false;
        $session->token = Bytes32::fromRandomBytes();
        $session->deviceFp = $deviceFp;
        $session->hmacSecret = $this->encryptHmacSecret($hmacSecret);
        $session->ipAddress = $ipAddress;
        $session->userAgent = substr($userAgent, 0, 255);
        $session->uid = $account->getAccountId();
        $session->issuedOn = time();
        $session->lastUsedOn = $session->issuedOn;
        $this->dbInsertAndSetId($session, "id");

        try {
            $this->dbUpdateChecksumAwareEntity(
                $session,
                new StringVector(),
                $session->id,
                "id",
                "checksum",
                false
            );
        } catch (NoChangesException) {
        }

        return $session;
    }

    /**
     * @param int|Bytes32 $sessionId
     * @param bool $useCache
     * @return SessionEntity
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function getSession(int|Bytes32 $sessionId, bool $useCache): SessionEntity
    {
        $storageKey = $sessionId instanceof Bytes32 ? "x" . $sessionId->toBase16() : $sessionId;
        /** @var SessionEntity $session */
        $session = $this->getEntity(
            $storageKey,
            $useCache,
            $sessionId instanceof Bytes32 ? "`token`=?" : "`id`=?",
            [$sessionId instanceof Bytes32 ? $sessionId->raw() : $sessionId],
            $useCache
        );

        return $session;
    }

    /**
     * @param int $timePeriod
     * @param AccountRealm $realm
     * @param SessionType $type
     * @param RealmAccountInterface|null $account
     * @param Bytes32|null $deviceFp
     * @param string|null $ipAddress
     * @return int
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function checkLastSessionCreatedWithin(
        int                    $timePeriod,
        AccountRealm           $realm,
        SessionType            $type,
        ?RealmAccountInterface $account,
        ?Bytes32               $deviceFp,
        ?string                $ipAddress
    ): int
    {
        [$whereQuery, $whereData] = $this->whereQueryFor($realm, $type, $account, $deviceFp, $ipAddress, false);
        $whereQuery .= " AND `issued_on`>=?";
        $whereData[] = time() - $timePeriod;
        return $this->table->queryFind($whereQuery, $whereData)->getCount();
    }

    /**
     * @param SessionEntity $session
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function updateLastActivity(SessionEntity $session): void
    {
        $session->lastUsedOn = time();
        $this->dbUpdateRow(["lastUsedOn" => $session->lastUsedOn], $session->id, "id");
    }

    /**
     * @param SessionEntity $session
     * @return void
     * @throws \Charcoal\App\Kernel\Entity\Exception\ChecksumComputeException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function archiveSession(SessionEntity $session): void
    {
        try {
            $session->archived = true;
            $this->dbUpdateChecksumAwareEntity($session, new StringVector("archived"), $session->id, "id", "checksum", false);
        } catch (NoChangesException) {
        }

        try {
            $this->deleteFromCache($session);
        } catch (CacheException) {
        }
    }

    /**
     * @param SessionEntity $session
     * @return void
     * @throws CacheException
     */
    public function deleteFromCache(SessionEntity $session): void
    {
        $this->cacheDeleteEntity($session);
    }

    /**
     * @param AccountRealm $realm
     * @param SessionType $type
     * @param RealmAccountInterface|null $account
     * @param Bytes32|null $deviceFp
     * @param string|null $ipAddress
     * @return array
     * @throws \Charcoal\Database\ORM\Exception\OrmException
     */
    public function findActiveSessionsFor(
        AccountRealm           $realm,
        SessionType            $type,
        ?RealmAccountInterface $account = null,
        ?Bytes32               $deviceFp = null,
        ?string                $ipAddress = null
    ): array
    {
        $whereQuery = $this->whereQueryFor($realm, $type, $account, $deviceFp, $ipAddress, true);
        return $this->table->queryFind($whereQuery[0], $whereQuery[1], sort: SortFlag::ASC, sortColumn: "id")->getAll();
    }

    /**
     * @param AccountRealm $realm
     * @param SessionType $type
     * @param RealmAccountInterface|null $account
     * @param Bytes32|null $deviceFp
     * @param string|null $ipAddress
     * @param bool $checkActiveOnly
     * @return array
     */
    private function whereQueryFor(
        AccountRealm           $realm,
        SessionType            $type,
        ?RealmAccountInterface $account = null,
        ?Bytes32               $deviceFp = null,
        ?string                $ipAddress = null,
        bool                   $checkActiveOnly = false,
    ): array
    {
        $whereQuery = "WHERE `realm`=? AND `type`=?";
        $whereData = [$realm->value, $type->value];
        if ($checkActiveOnly) {
            $whereQuery .= " AND `archived`=?";
            $whereData[] = 0;
        }

        // Selectors
        $selectors = [];
        if ($account) {
            if ($account->getRealm() !== $realm) {
                throw new \LogicException("Account realm does not match realm provided to find sessions for");
            }

            $selectors[] = "`uid`=?";
            $whereData[] = $account->getAccountId();
        }

        if ($deviceFp) {
            $selectors[] = "`fingerprint`=?";
            $whereData[] = $deviceFp->raw();
        }

        if ($ipAddress) {
            $selectors[] = "`ip_address`=?";
            $whereData[] = $ipAddress;
        }

        if (!$selectors) {
            throw new \LogicException("No selectors provided to find sessions for");
        }

        if (count($selectors) === 1) {
            $whereQuery .= " AND " . $selectors[0];
        } else {
            $whereQuery .= " AND (" . implode(" OR ", $selectors) . ")";
        }

        return [$whereQuery, $whereData];
    }
}