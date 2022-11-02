<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\DataStorage\Document;

use DateTime;
use Exception;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class DataStorageDocument
 *
 * @package Hanaboso\PipesPhpSdk\Storage\DataStorage\Document
 */
class DataStorageDocument
{

    /**
     * @var string|null
     */
    private ?string $user = '';

    /**
     * @var string|null
     */
    private ?string $application = '';

    /**
     * @var DateTime
     */
    private DateTime $created;

    /**
     * @var mixed
     */
    private mixed $data;

    /**
     * DataStorageDocument constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @param mixed $data
     *
     * @return DataStorageDocument
     * @throws Exception
     */
    public static function fromJson(mixed $data): DataStorageDocument {
        $document = new DataStorageDocument();
        $document->setUser($data['user']);
        $document->setApplication($data['application']);
        $document->setCreated(new DateTime($data['created']));
        $document->setData($data['data']);

        return $document;
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
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     *
     * @return DataStorageDocument
     */
    public function setData(mixed $data): DataStorageDocument
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
            'user' => $this->user,
            'application' => $this->application,
            'created' => $this->created->format(DateTimeUtils::DATE_TIME_GO),
            'data' => $this->data,
        ];
    }

    /**
     * @param DateTime $created
     *
     * @return $this
     */
    private function setCreated(DateTime $created): DataStorageDocument
    {
        $this->created = $created;

        return $this;
    }

}
