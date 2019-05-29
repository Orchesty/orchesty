<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Pipes
 *
 * @package Hanaboso\PipesFramework\Logs\Document
 *
 * @ODM\EmbeddedDocument()
 */
class Pipes
{

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $timestamp;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $type;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $hostname;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $channel;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $severity;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="correlation_id")
     */
    private $correlationId;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="topology_id")
     */
    private $topologyId;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="topology_name")
     */
    private $topologyName;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="node_id")
     */
    private $nodeId;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="node_name")
     */
    private $nodeName;

    /**
     * @var Stacktrace
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Logs\Document\Stacktrace")
     */
    private $stacktrace;

    /**
     * @return DateTime
     */
    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    /**
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
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
    public function getTopologyName(): string
    {
        return $this->topologyName;
    }

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
    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    /**
     * @return Stacktrace
     */
    public function getStacktrace(): Stacktrace
    {
        return $this->stacktrace;
    }

}
