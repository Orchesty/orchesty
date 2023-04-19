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
     * @var string
     */
    private string $id = '';

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
     * @return self
     * @throws Exception
     */
    public static function fromJson(mixed $data): self
    {
        $document = new self();
        $document
            ->setId($data['id'])
            ->setUser($data['user'])
            ->setApplication($data['application'])
            ->setCreated(new DateTime($data['created']))
            ->setData($data['data']);

        return $document;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): self
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
     * @return self
     */
    public function setUser(?string $user): self
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
     * @return self
     */
    public function setApplication(?string $application): self
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
     * @return self
     */
    public function setData(mixed $data): self
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
            'application' => $this->application,
            'created'     => $this->created->format(DateTimeUtils::DATE_TIME_GO),
            'data'        => $this->data,
            'id'          => $this->id,
            'user'        => $this->user,
        ];
    }

    /**
     * @param DateTime $created
     *
     * @return self
     */
    private function setCreated(DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

}
