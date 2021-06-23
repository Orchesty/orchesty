<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class ProcessesMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 * @ODM\Index(name="createdIndex", keys={"created"="desc"})
 */
class ProcessesMetricsFields
{

    /**
     * @var bool
     *
     * @ODM\Field(type="bool", name="counter_process_result")
     */
    private bool $success;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="counter_process_duration")
     */
    private int $duration;

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
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

}
