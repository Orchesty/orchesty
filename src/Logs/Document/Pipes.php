<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Document;

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
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private int $timestamp;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $type;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $hostname;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $channel;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $severity;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $level;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="correlationId")
     */
    private string $correlationId;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="topologyId")
     */
    private string $topologyId;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="topologyName")
     */
    private string $topologyName;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="nodeId")
     */
    private string $nodeId;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="nodeName")
     */
    private string $nodeName;

    /**
     * @var Stacktrace
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Logs\Document\Stacktrace")
     */
    private Stacktrace $stacktrace;

    /**
     * @return int
     */
    public function getTimestamp(): int
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
    public function getLevel(): string
    {
        return $this->level;
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
