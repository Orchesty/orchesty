<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\PipesFramework\UsageStats\Event;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class UsageStatsEvent
 *
 * @package Hanaboso\PipesFramework\UsageStats\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository", indexes={
 *     @ODM\Index(name="sortCreated", keys={"created"="asc"}),
 *     @ODM\Index(name="sortType", keys={"type"="asc"}),
 *     @ODM\Index(name="sortSent", keys={"sent"="asc"}),
 * })
 */
class UsageStatsEvent
{

    use IdTrait;
    use CreatedTrait;

    private const VERSION = 1;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $iid;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $type;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private int $version;

    /**
     * @var mixed[]
     *
     * @ODM\Field(type="hash")
     */
    private array $data;

    /**
     * @var int|null
     *
     * @ODM\Field(type="int")
     */
    private ?int $sent = NULL;

    /**
     * UsageStatsEvent constructor.
     *
     * @param string $iid
     * @param string $type
     *
     * @throws DateTimeException
     */
    public function __construct(string $iid, string $type)
    {
        $this->created = DateTimeUtils::getUtcDateTime();
        $this->iid     = $iid;
        $this->type    = $type;
        $this->version = self::VERSION;
    }

    /**
     * @param string             $iid
     * @param Event\BillingEvent $event
     *
     * @return UsageStatsEvent
     * @throws DateTimeException
     */
    public static function createFromBillingEvent(string $iid, Event\BillingEvent $event): self
    {
        /** @var AppInstallBillingData $data */
        $data = $event->getData();

        return (new self($iid, $event->getType()))->setAppInstallBillingData($data);
    }

    /**
     * @return string
     */
    public function getIid(): string
    {
        return $this->iid;
    }

    /**
     * @param string $iid
     *
     * @return $this
     */
    public function setIid(string $iid): self
    {
        $this->iid = $iid;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return $this
     */
    public function setVersion(int $version): self
    {
        $this->version = $version;

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
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param AppInstallBillingData $data
     *
     * @return $this
     */
    public function setAppInstallBillingData(AppInstallBillingData $data): self
    {
        $this->data = $data->toArray();

        return $this;
    }

    /**
     * @param OperationBillingData $data
     *
     * @return $this
     */
    public function setOperationBillingData(OperationBillingData $data): self
    {
        $this->data = $data->toArray();

        return $this;
    }

    /**
     * @param HearthBeatData $data
     *
     * @return $this
     */
    public function setHeartBeatData(HearthBeatData $data): self
    {
        $this->data = $data->toArray();

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSent(): ?int
    {
        return $this->sent;
    }

    /**
     * @param int|null $sent
     *
     * @return $this
     */
    public function setSent(?int $sent): self
    {
        $this->sent = $sent;

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
     * @return UsageStatsEvent
     */
    public function setCreated(DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created->format('Uu'),
            'data'    => $this->data,
            'iid'     => $this->iid,
            'type'    => $this->type,
            'version' => $this->version,
        ];
    }

}
