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
 */
#[ODM\Document(
    collection: 'MultiCounter',
    repositoryClass: 'Hanaboso\PipesFramework\Configurator\Repository\TopologyProgressRepository',
)]
#[ODM\Index(
    keys: ['created' => 'asc'],
    name: 'IK_multiCounter_created',
    expireAfterSeconds: 2_628_000,
)]
#[ODM\Index(
    keys: ['finished' => 'asc'],
    name: 'IK_multiCounter_finished',
)]
#[ODM\Index(
    keys: ['topologyId' => 'asc', 'created' => 'desc'],
    name: 'IK_multiCounter_topologyId_created',
)]
#[ODM\Index(
    keys: ['finished' => 'asc', 'created' => 'desc'],
    name: 'IK_multiCounter_finished_created',
)]
#[ODM\Index(
    keys: ['topologyId' => 'asc', 'finished' => 'asc', 'created' => 'desc'],
    name: 'IK_multiCounter_topologyId_finished_created',
)]
#[ODM\Index(
    keys: ['nok' => 'asc', 'finished' => 'asc', 'created' => 'desc'],
    name: 'IK_multiCounter_nok_finished_created',
)]
#[ODM\Index(
    keys: ['topologyId' => 'asc', 'nok' => 'asc', 'finished' => 'asc', 'created' => 'desc'],
    name: 'IK_multiCounter_topologyId_nok_finished_created',
)]
#[ODM\Index(
    keys: ['created' => 'asc', 'topologyId' => 'asc', 'nok' => 'asc', 'finished' => 'asc'],
    name: 'IK_multiCounter_created_topologyId_nok_finished',
)]
class TopologyProgress
{

    use IdTrait;

    /**
     * @var string
     */
    #[ODM\Id(type: 'string', strategy: 'NONE')]
    protected string $id;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $topologyId;

    /**
     * @var int
     */
    #[ODM\Field(type: 'int')]
    private int $ok = 0;

    /**
     * @var int
     */
    #[ODM\Field(type: 'int')]
    private int $nok = 0;

    /**
     * @var int
     */
    #[ODM\Field(type: 'int')]
    private int $total = 0;

    /**
     * @var int
     */
    #[ODM\Field(type: 'int')]
    private int $processedCount = 0;

    /**
     * @var DateTime
     */
    #[ODM\Field(name: 'created', type: 'date')]
    private DateTime $startedAt;

    /**
     * @var DateTime|null
     */
    #[ODM\Field(name: 'finished', type: 'date')]
    private ?DateTime $finishedAt = NULL;

    /**
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    private ?string $user = NULL;

    /**
     * @var bool
     */
    #[ODM\Field(type: 'bool')]
    private bool $terminated = FALSE;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $source = 'auto';

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
    public function getTopologyId(): string
    {
        return $this->topologyId;
    }

    /**
     * @param string $topologyId
     *
     * @return TopologyProgress
     */
    public function setTopologyId(string $topologyId): self
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
    public function setStartedAt(DateTime $startedAt): self
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
    public function setFinishedAt(?DateTime $finishedAt): self
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
    public function setOk(int $ok): self
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
    public function setNok(int $nok): self
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
    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return int
     */
    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    /**
     * @param int $processedCount
     *
     * @return TopologyProgress
     */
    public function setProcessedCount(int $processedCount): self
    {
        $this->processedCount = $processedCount;

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
     * @param string $user
     *
     * @return TopologyProgress
     */
    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTerminated(): bool
    {
        return $this->terminated;
    }

    /**
     * @param bool $terminated
     *
     * @return TopologyProgress
     */
    public function setTerminated(bool $terminated): self
    {
        $this->terminated = $terminated;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     *
     * @return TopologyProgress
     */
    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return mixed[]
     * @throws DateTimeException
     */
    public function toArray(): array
    {
        $finished = $this->finishedAt?->format(DateTimeUtils::DATE_TIME_UTC);
        $end      = $this->finishedAt ?? DateTimeUtils::getUtcDateTime();
        $count    = $this->ok + $this->nok;

        return [
            'correlationId'  => $this->id,
            'duration'       => self::durationInMs($this->startedAt, $end),
            'failed'         => $this->nok,
            'finished'       => $finished,
            'id'             => $this->topologyId,
            'nodesProcessed' => $count,
            'nodesTotal'     => $this->total,
            'source'         => $this->source,
            'started'        => $this->startedAt->format(DateTimeUtils::DATE_TIME_UTC),
            'status'         => $this->terminated ? 'TERMINATED' : ($count < $this->total ? 'IN PROGRESS' : ($this->nok > 0 ? 'FAILED' : 'SUCCESS')),
            'user'           => $this->user ?? '',
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
        return  (int) $end->format('Uv') - (int) $start->format('Uv');
    }

}
