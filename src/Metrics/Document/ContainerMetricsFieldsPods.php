<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\Utils\Date\DateTimeUtils;

/**
 * Class ContainerMetricsFieldsPods
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 */
class ContainerMetricsFieldsPods
{

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
    private int $restarts;

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
    public function getRestarts(): int
    {
        return $this->restarts;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'created'  => $this->created->format(DateTimeUtils::DATE_TIME_UTC),
            'message'  => $this->message,
            'restarts' => $this->restarts,
            'up'       => $this->up,
        ];
    }

}
