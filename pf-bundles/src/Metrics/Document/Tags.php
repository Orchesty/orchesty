<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Tags
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\EmbeddedDocument]
class Tags
{

    public const string NODE_ID        = 'node_id';
    public const string TOPOLOGY_ID    = 'topology_id';
    public const string QUEUE          = 'queue';
    public const string APPLICATION_ID = 'application_id';
    public const string USER_ID        = 'user_id';
    public const string CORRELATION_ID = 'correlation_id';

    public const array BRIDGE_TAGS = self::MONOLITH_TAGS;

    public const array MONOLITH_TAGS = [
        self::NODE_ID,
        self::TOPOLOGY_ID,
    ];

    public const array CONNECTOR_TAGS = [
        self::NODE_ID,
        self::TOPOLOGY_ID,
        self::APPLICATION_ID,
        self::USER_ID,
        self::CORRELATION_ID,
    ];

    public const array PROCESS_TAGS = [
        self::NODE_ID,
    ];

    public const array RABBIT_TAGS = [
        self::QUEUE,
    ];

    /**
     * @var string
     */
    #[ODM\Field(name: 'node_id', type: 'string')]
    private string $nodeId;

    /**
     * @var string
     */
    #[ODM\Field(name: 'topology_id', type: 'string')]
    private string $topologyId;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $queue;

    /**
     * @return string
     */
    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    /**
     * @return string
     */
    public function getTopologyId(): string
    {
        return $this->topologyId;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @param string $nodeId
     *
     * @return $this
     */
    public function setNodeId(string $nodeId): self
    {
        $this->nodeId = $nodeId;

        return $this;
    }

    /**
     * @param string $topologyId
     *
     * @return $this
     */
    public function setTopologyId(string $topologyId): self
    {
        $this->topologyId = $topologyId;

        return $this;
    }

    /**
     * @param string $queue
     *
     * @return Tags
     */
    public function setQueue(string $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

}
