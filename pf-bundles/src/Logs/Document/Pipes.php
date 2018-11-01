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
     * @ODM\Field(type="string")
     */
    private $correlation_id;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $topology_id;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $topology_name;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $node_id;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $node_name;

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
        return $this->correlation_id;
    }

    /**
     * @return string
     */
    public function getTopologyId(): string
    {
        return $this->topology_id;
    }

    /**
     * @return string
     */
    public function getTopologyName(): string
    {
        return $this->topology_name;
    }

    /**
     * @return string
     */
    public function getNodeId(): string
    {
        return $this->node_id;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return $this->node_name;
    }

    /**
     * @return Stacktrace
     */
    public function getStacktrace(): Stacktrace
    {
        return $this->stacktrace;
    }

}