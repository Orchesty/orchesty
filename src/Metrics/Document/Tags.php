<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Tags
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 */
class Tags
{

    public const NODE_ID     = 'node_id';
    public const TOPOLOGY_ID = 'topology_id';
    public const QUEUE       = 'queue';

    public const BRIDGE_TAGS    = self::MONOLITH_TAGS;
    public const CONNECTOR_TAGS = self::MONOLITH_TAGS;
    public const MONOLITH_TAGS  = [
        self::NODE_ID,
        self::TOPOLOGY_ID,
    ];

    public const PROCESS_TAGS = [
        self::NODE_ID,
    ];

    public const RABBIT_TAGS = [
        self::QUEUE,
    ];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $nodeId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $topologyId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $queue;

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

}
