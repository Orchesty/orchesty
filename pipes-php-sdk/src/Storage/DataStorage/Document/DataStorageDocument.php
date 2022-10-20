<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\DataStorage\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class DataStorageDocument
 *
 * @package Hanaboso\PipesPhpSdk\Storage\DataStorage\Document
 *
 * @ODM\Document()
 */
class DataStorageDocument
{

    use IdTrait;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    private ?string $user = '';

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    private ?string $application = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    private string $processId = '';

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     * @ODM\Index(expireAfterSeconds=86400)
     */
    private DateTime $created;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $data = '';

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): DataStorageDocument
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @param string|null $user
     *
     * @return DataStorageDocument
     */
    public function setUser(?string $user): DataStorageDocument
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplication(): ?string
    {
        return $this->application;
    }

    /**
     * @param string|null $application
     *
     * @return DataStorageDocument
     */
    public function setApplication(?string $application): DataStorageDocument
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @return string
     */
    public function getProcessId(): string
    {
        return $this->processId;
    }

    /**
     * @param string $processId
     *
     * @return DataStorageDocument
     */
    public function setProcessId(string $processId): DataStorageDocument
    {
        $this->processId = $processId;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     *
     * @return $this
     */
    public function setCreated(DateTime $created): DataStorageDocument
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return DataStorageDocument
     */
    public function setData(string $data): DataStorageDocument
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'processId' => $this->processId,
            'data' => $this->data,
            'application' => $this->application,
            'user' => $this->user,
        ];
    }

}
