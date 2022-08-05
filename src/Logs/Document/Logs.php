<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class Logs
 *
 * @package Hanaboso\PipesFramework\Logs\Document
 *
 * @ODM\Document()
 * @ODM\Index(name="SearchIndex", keys={"message"="text", "pipes.correlationId"="text", "pipes.topologyId"="text", "pipes.topologyName"="text", "pipes.nodeId"="text", "pipes.nodeName"="text"}),
 * @ODM\Index(name="SeverityIndex", keys={"pipes.severity"="hashed"}),
 * @ODM\Index(name="LevelIndex", keys={"pipes.level"="hashed"}),
 * @ODM\Index(name="LogsTimestampIndex", keys={"ts"="desc"})
 * @ODM\Index(name="expireIndex", keys={"ts"=1}, options={"expireAfterSeconds"=2628000})
 */
class Logs
{

    use IdTrait;

    public const ID       = 'id';
    public const MONGO_ID = '_id';

    public const TIMESTAMP = 'ts';
    public const MESSAGE   = 'message';

    public const PIPES_TYPE           = 'pipes.type';
    public const PIPES_SEVERITY       = 'pipes.severity';
    public const PIPES_CORRELATION_ID = 'pipes.correlation_id';
    public const PIPES_TOPOLOGY_ID    = 'pipes.topology_id';
    public const PIPES_NODE_ID        = 'pipes.node_id';
    public const PIPES_TIME_MARGIN    = 'pipes.time_margin';
    public const PIPES_TIMESTAMP      = 'pipes.timestamp';

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date", name="ts")
     */
    private DateTime $timestamp;

    /**
     * @var Pipes
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Logs\Document\Pipes")
     */
    private Pipes $pipes;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="@version")
     */
    private string $version;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $message;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $host;

    /**
     * @return DateTime
     */
    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    /**
     * @return Pipes
     */
    public function getPipes(): Pipes
    {
        return $this->pipes;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

}
