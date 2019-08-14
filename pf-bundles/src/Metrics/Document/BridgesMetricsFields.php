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
 */
class BridgesMetricsFields
{

    /**
     * @var bool
     *
     * @ODM\Field(type="bool", name="bridge_job_result_success")
     */
    private $success;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="bridge_job_waiting_duration")
     */
    private $waitingDuration;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="bridge_job_total_duration")
     */
    private $totalDuration;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $created;

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
