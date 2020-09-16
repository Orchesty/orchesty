<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\Utils\Date\DateTimeUtils;

/**
 * Class TopologyProgress
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 *
 * @ODM\Document(collection="Progress")
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
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $topologyName;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $status;

    /**
     * @var int
     *
     * @ODM\Field(type="integer")
     */
    private int $followers;

    /**
     * @var int
     *
     * @ODM\Field(type="integer")
     */
    private int $duration;

    /**
     * @var int
     *
     * @ODM\Field(type="integer")
     */
    private int $startedAt;

    /**
     * @var int|null
     *
     * @ODM\Field(type="integer")
     */
    private ?int $finishedAt = null;

    /**
     * @var Collection<string, NodeProgress>
     *
     * @ODM\EmbedMany(targetDocument=NodeProgress::class)
     */
    private $nodes;

    /**
     * TopologyProgress constructor.
     */
    public function __construct()
    {
        $this->nodes = new ArrayCollection();
    }

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
     * @return string
     */
    public function getTopologyName(): string
    {
        return $this->topologyName;
    }

    /**
     * @param string $topologyName
     *
     * @return TopologyProgress
     */
    public function setTopologyName(string $topologyName): TopologyProgress
    {
        $this->topologyName = $topologyName;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return TopologyProgress
     */
    public function setStatus(string $status): TopologyProgress
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getFollowers(): int
    {
        return $this->followers;
    }

    /**
     * @param int $followers
     *
     * @return TopologyProgress
     */
    public function setFollowers(int $followers): TopologyProgress
    {
        $this->followers = $followers;

        return $this;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     *
     * @return TopologyProgress
     */
    public function setDuration(int $duration): TopologyProgress
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return int
     */
    public function getStartedAt(): int
    {
        return $this->startedAt;
    }

    /**
     * @param int $startedAt
     *
     * @return TopologyProgress
     */
    public function setStartedAt(int $startedAt): TopologyProgress
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFinishedAt(): ?int
    {
        return $this->finishedAt;
    }

    /**
     * @param int|null $finishedAt
     *
     * @return TopologyProgress
     */
    public function setFinishedAt(?int $finishedAt): TopologyProgress
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /**
     * @return Collection<string, NodeProgress>
     */
    public function getNodes(): Collection
    {
        return $this->nodes;
    }

    /**
     * @param NodeProgress $node
     *
     * @return $this
     */
    public function addNode(NodeProgress $node): TopologyProgress
    {
        $this->nodes->add($node);

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $finished = $this->finishedAt ?
            DateTimeUtils::getUtcDateTimeFromTimeStamp($this->finishedAt)->format(DateTimeUtils::DATE_TIME) :
            NULL;
        $count    = $this->nodes->count();
        $nodes    = [];
        foreach ($this->nodes as $node) {
            $nodes[] = $node->toArray();
        }

        return [
            'id'             => $this->topologyId,
            'name'           => $this->topologyName,
            'correlationId'  => $this->correlationId,
            'duration'       => $this->duration,
            'status'         => $this->status,
            'nodesProcessed' => $count,
            'nodesRemaining' => $this->followers,
            'nodesTotal'     => $this->followers + $count,
            'nodes'          => $nodes,
            'started'        => DateTimeUtils::getUtcDateTimeFromTimeStamp($this->startedAt)->format(
                DateTimeUtils::DATE_TIME
            ),
            'finished'       => $finished,
        ];
    }

}
