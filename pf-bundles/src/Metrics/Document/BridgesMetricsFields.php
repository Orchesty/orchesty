<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class BridgesMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 * @ODM\Index(name="createdIndex", keys={"created"="desc"})
 */
class BridgesMetricsFields
{

    // TODO unused metrics workerDuration

    /**
     * @var bool
     *
     * @ODM\Field(type="bool", name="result_success")
     */
    private bool $success;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="waiting_duration")
     */
    private int $waitingDuration;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="total_duration")
     */
    private int $totalDuration;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private DateTime $created;

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return int
     */
    public function getWaitingDuration(): int
    {
        return $this->waitingDuration;
    }

    /**
     * @return int
     */
    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

}
