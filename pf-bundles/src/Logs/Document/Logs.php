<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class Logs
 *
 * @package Hanaboso\PipesFramework\Logs\Document
 */
#[ODM\Document]
#[ODM\Index(
    keys: [
        'message' =>'text',
        'pipes.correlationId' =>'text',
        'pipes.nodeId' =>'text',
        'pipes.nodeName' =>'text',
        'pipes.topologyId' =>'text',
        'pipes.topologyName' =>'text',
    ],
    name: 'SearchIndex',
)]
#[ODM\Index(keys: ['pipes.severity' => 'hashed'], name: 'SeverityIndex')]
#[ODM\Index(keys: ['pipes.level' => 'hashed'], name: 'LevelIndex')]
#[ODM\Index(keys: ['ts' => 'desc'], name: 'LogsTimestampIndex')]
#[ODM\Index(keys: ['ts' => 'asc'], name: 'expireIndex', expireAfterSeconds: 2_628_000)]
class Logs
{

    use IdTrait;

    public const string ID       = 'id';
    public const string MONGO_ID = '_id';

    public const string TIMESTAMP = 'ts';
    public const string MESSAGE   = 'message';

    public const string PIPES_SERVICE        = 'pipes.service';
    public const string PIPES_SEVERITY       = 'pipes.severity';
    public const string PIPES_CORRELATION_ID = 'pipes.correlation_id';
    public const string PIPES_TOPOLOGY_ID    = 'pipes.topology_id';
    public const string PIPES_NODE_ID        = 'pipes.node_id';
    public const string PIPES_TIME_MARGIN    = 'pipes.time_margin';
    public const string PIPES_TIMESTAMP      = 'pipes.timestamp';
    public const string PIPES_USER_ID        = 'pipes.user_id';

    /**
     * @var DateTime
     */
    #[ODM\Field(name: 'ts', type: 'date')]
    private DateTime $timestamp;

    /**
     * @var Pipes
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Logs\Document\Pipes')]
    private Pipes $pipes;

    /**
     * @var string
     */
    #[ODM\Field(name: '@version', type: 'string')]
    private string $version;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $message;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
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
