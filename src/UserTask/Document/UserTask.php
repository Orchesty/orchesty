<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Exception;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\UpdatedTrait;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\String\Json;
use stdClass;

/**
 * Class UserTask
 *
 * @package Hanaboso\PipesFramework\UserTask\Document
 */
#[ODM\Document(repositoryClass: 'Hanaboso\PipesFramework\UserTask\Repository\UserTaskRepository')]
#[ODM\Index(
    keys: ['created' => 'asc'],
    name: 'IK_userTask_created',
    expireAfterSeconds: 2_678_400,
)]
#[ODM\Index(
    keys: ['type' => 'asc', 'created' => 'desc'],
    name: 'IK_userTask_type_created',
)]
#[ODM\Index(
    keys: ['type' => 'asc', 'nodeId' => 'asc', 'created' => 'desc'],
    name: 'IK_userTask_type_nodeId_created',
)]
#[ODM\Index(
    keys: ['type' => 'asc', 'topologyId' => 'asc', 'created' => 'desc'],
    name: 'IK_userTask_type_topologyId_created',
)]
#[ODM\Index(
    keys: ['type' => 'asc', 'correlationId' => 'asc', 'created' => 'desc'],
    name: 'IK_userTask_type_correlationId_created',
)]
#[ODM\Index(
    keys: ['type' => 'asc', 'nodeId' => 'asc', 'topologyId' => 'asc', 'created' => 'desc'],
    name: 'IK_userTask_type_nodeId_topologyId_created',
)]
#[ODM\Index( // Used by \Hanaboso\PipesFramework\Configurator\Model\ProcessManager::getProcessDetail
    keys: ['correlationId' => 'asc'],
    name: 'IK_userTask_correlationId',
)]
#[ODM\Index( // Used by \Hanaboso\PipesFramework\Metrics\Model\Filters\MetricUserTaskAggregationFilter
    keys: ['created' => 'desc', 'message.headers.node-id' => 'asc', 'message.headers.topology-id' => 'asc', 'message.headers.result-message' => 'asc'],
    name: 'IK_userTask_created_messageHeadersNodeId_messageHeadersTopologyId_messageHeadersResultMessage',
)]
    #[ODM\Index( // Used by Limiter (pkg/metrics/client.go)
        keys: ['type' => 'asc', 'created' => 'asc', 'message.headers.node-id' => 'asc', 'message.headers.node-name' => 'asc', 'message.headers.user' => 'asc', 'message.headers.topology-id' => 'asc', 'message.headers.application' => 'asc'],
        name: 'IK_userTask_type_created_messageHeadersNodeId_messageHeadersNodeName_messageHeadersUser_messageHeadersTopologyId_messageHeadersApplication',
    )]
    #[ODM\Index(
        keys: ['type' => 'asc', 'identity' => 'asc', 'created' => 'desc'],
        name: 'IK_userTask_type_identity_created',
    )]
    #[ODM\Index( // Used by the MCP trash tools: newest first with a stable tie-breaker for paging
        keys: ['type' => 'asc', 'created' => 'desc', '_id' => 'desc'],
        name: 'IK_userTask_type_created_id',
    )]
#[ODM\HasLifecycleCallbacks]
class UserTask
{

    use CreatedTrait;
    use IdTrait;
    use UpdatedTrait;

    public const string ID                 = 'id';
    public const string NODE_ID            = 'nodeId';
    public const string TOPOLOGY_ID        = 'topologyId';
    public const string NODE_NAME          = 'nodeName';
    public const string TOPOLOGY_NAME      = 'topologyName';
    public const string TOPOLOGY_DESCR     = 'topologyDescr';
    public const string TOPOLOGY_VERSION   = 'topologyVersion';
    public const string TOPOLOGY_DELETED   = 'topologyDeleted';
    public const string CORRELATION_ID     = 'correlationId';
    public const string TYPE               = 'type';
    public const string RETURN_EXCHANGE    = 'returnExchange';
    public const string RETURN_ROUTING_KEY = 'returnRoutingKey';
    public const string MESSAGE            = 'message';
    public const string AUDIT_LOGS         = 'auditLogs';
    public const string CREATED            = 'created';
    public const string UPDATED            = 'updated';
    public const string USER               = 'user';
    public const string IDENTITY           = 'identity';
    public const string QUESTION           = 'question';
    public const string DISPLAY            = 'display';
    public const string REPLY_ID           = 'replyId';
    public const string SCHEMA             = 'schema';

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $nodeId;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $topologyId;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $nodeName;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $topologyName;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $type;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $returnExchange;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $returnRoutingKey;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $correlationId;

    /**
     * @var UserTaskMessage
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage')]
    private UserTaskMessage $message;

    /**
     * @var mixed[]|string
     */
    #[ODM\Field(type: 'string')]
    private array|string $auditLogs = [];

    /**
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    private ?string $user = NULL;

    /**
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    private ?string $identity = NULL;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $question = '';

    /**
     * @var mixed[]
     */
    #[ODM\Field(type: 'hash')]
    private array $display = [];

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $replyId = '';

    /**
     * JSON Schema object of the fields a Question task asks for.
     *
     * @var mixed[]
     */
    #[ODM\Field(type: 'hash')]
    private array $schema = [];

    /**
     * UserTask constructor.
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
        $this->updated = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return string
     */
    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    /**
     * @param string $nodeId
     *
     * @return UserTask
     */
    public function setNodeId(string $nodeId): self
    {
        $this->nodeId = $nodeId;

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
     * @return UserTask
     */
    public function setTopologyId(string $topologyId): self
    {
        $this->topologyId = $topologyId;

        return $this;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    /**
     * @param string $nodeName
     *
     * @return UserTask
     */
    public function setNodeName(string $nodeName): self
    {
        $this->nodeName = $nodeName;

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
     * @return UserTask
     */
    public function setTopologyName(string $topologyName): self
    {
        $this->topologyName = $topologyName;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return UserTask
     * @throws EnumException
     */
    public function setType(string $type): self
    {
        if (!UserTaskEnum::tryFrom($type)) {
            throw new EnumException();
        }
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getReturnExchange(): string
    {
        return $this->returnExchange;
    }

    /**
     * @param string $returnExchange
     *
     * @return UserTask
     */
    public function setReturnExchange(string $returnExchange): self
    {
        $this->returnExchange = $returnExchange;

        return $this;
    }

    /**
     * @return string
     */
    public function getReturnRoutingKey(): string
    {
        return $this->returnRoutingKey;
    }

    /**
     * @param string $returnRoutingKey
     *
     * @return UserTask
     */
    public function setReturnRoutingKey(string $returnRoutingKey): self
    {
        $this->returnRoutingKey = $returnRoutingKey;

        return $this;
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
     * @return UserTask
     */
    public function setCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * @return UserTaskMessage
     */
    public function getMessage(): UserTaskMessage
    {
        return $this->message;
    }

    /**
     * @param UserTaskMessage $message
     *
     * @return UserTask
     */
    public function setMessage(UserTaskMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return mixed[]|string
     */
    public function getAuditLogs(): array|string
    {
        if (!is_array($this->auditLogs)) {
            $this->auditLogs = Json::decode($this->auditLogs);
        }

        return $this->auditLogs;
    }

    /**
     * @param mixed[]|string $auditLogs
     *
     * @return UserTask
     */
    public function setAuditLogs(array|string $auditLogs): self
    {
        $this->auditLogs = $auditLogs;

        return $this;
    }

    /**
     * @param mixed[] $data
     *
     * @return UserTask
     */
    public function addAuditLog(array $data): self
    {
        /** @var mixed[] $arr */
        $arr             = $this->getAuditLogs(); // Ensure array should someone call flush() before this
        $arr[]           = $data;
        $this->auditLogs = $arr;

        return $this;
    }

    /**
     * @throws Exception
     */
    #[ODM\PreFlush]
    public function preFlush(): void
    {
        if (is_array($this->auditLogs)) {
            $this->auditLogs = Json::encode($this->auditLogs);
        }
    }

    /**
     *
     */
    #[ODM\PostLoad]
    public function postLoad(): void
    {
        $this->getAuditLogs();
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
     * @return UserTask
     */
    public function setUser(string $user): self {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentity(): ?string
    {
        return $this->identity;
    }

    /**
     * @param string|null $identity
     *
     * @return self
     */
    public function setIdentity(?string $identity): self
    {
        $this->identity = $identity;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * @param string $question
     *
     * @return self
     */
    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getDisplay(): array
    {
        return $this->display;
    }

    /**
     * @param mixed[] $display
     */
    public function setDisplay(array $display): self
    {
        $this->display = $display;

        return $this;
    }

    /**
     * @return string
     */
    public function getReplyId(): string
    {
        return $this->replyId;
    }

    /**
     * @param string $replyId
     *
     * @return self
     */
    public function setReplyId(string $replyId): self
    {
        $this->replyId = $replyId;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * @param mixed[] $schema
     *
     * @return self
     */
    public function setSchema(array $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPulseTask(): bool
    {
        return in_array($this->type, [UserTaskEnum::CONFIRM->value, UserTaskEnum::QUESTION->value], TRUE);
    }

    /**
     * Empty display / schema are JSON objects, so typed clients (Trace) can decode them.
     *
     * @return mixed[]
     */
    public function toPulseArray(): array
    {
        return [
            self::CREATED       => $this->created->format(DateTimeUtils::DATE_TIME_UTC),
            self::DISPLAY       => $this->getDisplay() === [] ? new stdClass() : $this->getDisplay(),
            self::ID            => $this->id,
            self::NODE_NAME     => $this->nodeName,
            self::QUESTION      => $this->getQuestion(),
            self::SCHEMA        => $this->getSchema() === [] ? new stdClass() : $this->getSchema(),
            self::TOPOLOGY_NAME => $this->topologyName,
            self::TYPE          => $this->type,
        ];
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $data = [
            self::AUDIT_LOGS     => $this->getAuditLogs(),
            self::CORRELATION_ID => $this->correlationId,
            self::CREATED        => $this->created->format(DateTimeUtils::DATE_TIME_UTC),
            self::ID             => $this->id,
            self::MESSAGE        => $this->message->toPublicArray(),
            self::NODE_ID        => $this->nodeId,
            self::NODE_NAME      => $this->nodeName,
            self::TOPOLOGY_ID    => $this->topologyId,
            self::TOPOLOGY_NAME  => $this->topologyName,
            self::TYPE           => $this->type,
            self::UPDATED        => $this->updated->format(DateTimeUtils::DATE_TIME_UTC),
            self::USER           => $this->getUser(),
        ];

        // Pulse fields only exist on live Confirm / Question tasks (trash and breakpoints don't carry them)
        if ($this->isPulseTask()) {
            $data[self::DISPLAY]  = $this->getDisplay();
            $data[self::IDENTITY] = $this->getIdentity();
            $data[self::QUESTION] = $this->getQuestion();
            $data[self::SCHEMA]   = $this->getSchema();
        }

        return $data;
    }

}
