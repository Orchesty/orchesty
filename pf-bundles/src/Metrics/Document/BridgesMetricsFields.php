<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class BridgesMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\EmbeddedDocument]
class BridgesMetricsFields
{

    public const string CREATED = 'created';

    /**
     * @var bool
     */
    #[ODM\Field(name: 'result_success', type: 'bool')]
    private bool $success;

    /**
     * @var int
     */
    #[ODM\Field(name: 'waiting_duration', type: 'int')]
    private int $waitingDuration;

    /**
     * @var int
     */
    #[ODM\Field(name: 'worker_duration', type: 'int')]
    private int $workerDuration;

    /**
     * @var int
     */
    #[ODM\Field(name: 'total_duration', type: 'int')]
    private int $totalDuration;

    /**
     * @var DateTime
     */
    #[ODM\Field(type: 'date')]
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
    public function getWorkerDuration(): int
    {
        return $this->workerDuration;
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
