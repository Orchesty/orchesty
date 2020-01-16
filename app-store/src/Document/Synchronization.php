<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\UpdatedTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class Synchronization
 *
 * @package Hanaboso\HbPFAppStore\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\HbPFAppStore\Repository\SynchronizationRepository")
 * @ODM\HasLifecycleCallbacks()
 */
class Synchronization
{

    public const KEY                  = 'key';
    public const USER                 = 'user';
    public const STATUS               = 'status';
    public const INTERNAL_ID          = 'internalId';
    public const INTERNAL_ID_HEADER   = 'internal-id';
    public const EXTERNAL_ID          = 'externalId';
    public const EXTERNAL_ID_HEADER   = 'external-id';
    public const INTERNAL_HASH        = 'internalHash';
    public const INTERNAL_HASH_HEADER = 'internal-hash';
    public const EXTERNAL_HASH        = 'externalHash';
    public const EXTERNAL_HASH_HEADER = 'external-hash';
    public const DATA                 = 'data';

    use IdTrait;
    use CreatedTrait;
    use UpdatedTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $key = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $user = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $status = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $internalId = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $externalId = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $internalHash = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $externalHash = '';

    /**
     * @var mixed[]
     *
     * @ODM\Field(type="hash")
     */
    private $data = [];

    /**
     * Synchronization constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
        $this->updated = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return Synchronization
     */
    public function setKey(string $key): Synchronization
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return Synchronization
     */
    public function setUser(string $user): Synchronization
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Synchronization
     */
    public function setStatus(string $status): Synchronization
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalId(): string
    {
        return $this->internalId;
    }

    /**
     * @param string $internalId
     *
     * @return Synchronization
     */
    public function setInternalId(string $internalId): Synchronization
    {
        $this->internalId = $internalId;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     *
     * @return Synchronization
     */
    public function setExternalId(string $externalId): Synchronization
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalHash(): string
    {
        return $this->internalHash;
    }

    /**
     * @param string $internalHash
     *
     * @return Synchronization
     */
    public function setInternalHash(string $internalHash): Synchronization
    {
        $this->internalHash = $internalHash;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalHash(): string
    {
        return $this->externalHash;
    }

    /**
     * @param string $externalHash
     *
     * @return Synchronization
     */
    public function setExternalHash(string $externalHash): Synchronization
    {
        $this->externalHash = $externalHash;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param mixed[] $data
     *
     * @return Synchronization
     */
    public function setData(array $data): Synchronization
    {
        $this->data = $data;

        return $this;
    }

}
