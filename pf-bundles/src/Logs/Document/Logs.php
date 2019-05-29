<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;

/**
 * Class Logs
 *
 * @package Hanaboso\PipesFramework\Logs\Document
 *
 * @ODM\Document()
 * @ODM\Indexes({
 *     @ODM\Index(name="SearchIndex", keys={"message"="text", "pipes.correlation_id"="text", "pipes.topology_id"="text", "pipes.topology_name"="text", "pipes.node_id"="text", "pipes.node_name"="text"}),
 *     @ODM\Index(name="SeverityIndex", keys={"pipes.severity"="hashed"})
 * })
 */
class Logs
{

    public const ID       = 'id';
    public const MONGO_ID = '_id';

    public const TIMESTAMP = 'timestamp';
    public const MESSAGE   = 'message';

    public const PIPES_TYPE           = 'pipes.type';
    public const PIPES_SEVERITY       = 'pipes.severity';
    public const PIPES_CORRELATION_ID = 'pipes.correlation_id';
    public const PIPES_TOPOLOGY_ID    = 'pipes.topology_id';
    public const PIPES_TOPOLOGY_NAME  = 'pipes.topology_name';
    public const PIPES_NODE_ID        = 'pipes.node_id';
    public const PIPES_NODE_NAME      = 'pipes.node_name';

    use IdTrait;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date", name="@timestamp")
     */
    private $timestamp;

    /**
     * @var Pipes
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Logs\Document\Pipes")
     */
    private $pipes;

    /**
     * @var string
     *
     * @ODM\Field(type="string", name="@version")
     */
    private $version;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $message;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $host;

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
