<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\Utils\Date\DateTimeUtils;

/**
 * Class ContainerMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 */
class ContainerMetricsFields
{

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $name;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $message;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    private bool $up;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private DateTime $created;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private int $desired = 0;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private int $ready = 0;

    /**
     * @var Collection<int, ContainerMetricsFieldsPods>
     *
     * @ODM\EmbedMany(targetDocument="Hanaboso\PipesFramework\Metrics\Document\ContainerMetricsFieldsPods")
     */
    private Collection $pods;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isUp(): bool
    {
        return $this->up;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getDesired(): int
    {
        return $this->desired;
    }

    /**
     * @return int
     */
    public function getReady(): int
    {
        return $this->ready;
    }

    /**
     * @return ContainerMetricsFieldsPods[]
     */
    public function getPods(): array
    {
        return $this->pods->toArray();
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'name'    => $this->name,
            'message' => $this->message,
            'up'      => $this->up,
            'created' => $this->created->format(DateTimeUtils::DATE_TIME_UTC),
            'desired' => $this->desired,
            'ready'   => $this->ready,
            'pods'    => array_map(
                static fn(ContainerMetricsFieldsPods $pod): array => $pod->toArray(),
                $this->getPods(),
            ),
        ];
    }

}
