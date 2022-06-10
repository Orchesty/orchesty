<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class TopologyProgress
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 *
 * @ODM\Document(collection="MultiCounter")
 */
class TopologyProgress
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $correlationId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $topologyId;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private int $ok = 0;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private int $nok = 0;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private int $total = 0;

    /**
     * @var DateTime
     *
     * @ODM\Field(name="created", type="date")
     */
    private DateTime $startedAt;

    /**
     * @var DateTime|null
     *
     * @ODM\Field(name="finished", type="date")
     */
    private ?DateTime $finishedAt = NULL;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * @param string $correlationId
     *
     * @return TopologyProgress
     */
    public function setCorrelationId(string $correlationId): TopologyProgress
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTopologyId(): string
    {
        return $this->topologyId;
    }

    /**
     * @param string $topologyId
     *
     * @return TopologyProgress
     */
    public function setTopologyId(string $topologyId): TopologyProgress
    {
        $this->topologyId = $topologyId;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartedAt(): DateTime
    {
        return $this->startedAt;
    }

    /**
     * @param DateTime $startedAt
     *
     * @return TopologyProgress
     */
    public function setStartedAt(DateTime $startedAt): TopologyProgress
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt;
    }

    /**
     * @param DateTime|null $finishedAt
     *
     * @return TopologyProgress
     */
    public function setFinishedAt(?DateTime $finishedAt): TopologyProgress
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getOk(): int
    {
        return $this->ok;
    }

    /**
     * @param int $ok
     *
     * @return TopologyProgress
     */
    public function setOk(int $ok): TopologyProgress
    {
        $this->ok = $ok;

        return $this;
    }

    /**
     * @return int
     */
    public function getNok(): int
    {
        return $this->nok;
    }

    /**
     * @param int $nok
     *
     * @return TopologyProgress
     */
    public function setNok(int $nok): TopologyProgress
    {
        $this->nok = $nok;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     *
     * @return TopologyProgress
     */
    public function setTotal(int $total): TopologyProgress
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return mixed[]
     * @throws DateTimeException
     */
    public function toArray(): array
    {
        $finished = $this->finishedAt ? $this->finishedAt->format(DateTimeUtils::DATE_TIME_UTC) : NULL;
        $end      = $this->finishedAt ?? DateTimeUtils::getUtcDateTime();
        $count    = $this->ok + $this->nok;

        return [
            'id'             => $this->topologyId,
            'correlationId'  => $this->correlationId,
            'duration'       => TopologyProgress::durationInMs($this->startedAt, $end),
            'started'        => $this->startedAt->format(DateTimeUtils::DATE_TIME_UTC),
            'finished'       => $finished,
            'nodesProcessed' => $count,
            'nodesTotal'     => $this->total,
            'status'         => $count < $this->total ? 'IN PROGRESS' : ($this->nok > 0 ? 'FAILED' : 'SUCCESS'),
            'failed'         => $this->nok,
        ];
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return int
     */
    public static function durationInMs(DateTime $start, DateTime $end): int
    {
        $startSecs = $start->getTimestamp() * 1_000;
        $endSecs   = $end->getTimestamp() * 1_000;
        $startMs   = (int) ($start->format('u') / 1_000);
        $endMs     = (int) ($end->format('u') / 1_000);

        return $endSecs - $startSecs + $endMs - $startMs;
    }

}
