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
#[ODM\Index( // Used by \Hanaboso\PipesFramework\Metrics\Model\Filters\MetricUserTaskAggregationFilter
    keys: ['created' => 'desc', 'message.headers.node-id' => 'asc', 'message.headers.topology-id' => 'asc', 'message.headers.result-message' => 'asc'],
    name: 'IK_userTask_created_messageHeadersNodeId_messageHeadersTopologyId_messageHeadersResultMessage',
)]
#[ODM\Index( // Used by Limiter (pkg/metrics/client.go)
    keys: ['type' => 'asc', 'message.headers.node-id' => 'asc', 'message.headers.node-name' => 'asc', 'message.headers.user' => 'asc', 'message.headers.topology-id' => 'asc', 'message.headers.application' => 'asc'],
    name: 'IK_userTask_type_messageHeadersNodeId_messageHeadersNodeName_messageHeadersUser_messageHeadersTopologyId_messageHeadersApplication',
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
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::AUDIT_LOGS     => $this->getAuditLogs(),
            self::CORRELATION_ID => $this->correlationId,
            self::CREATED        => $this->created->format(DateTimeUtils::DATE_TIME_UTC),
            self::ID             => $this->id,
            self::MESSAGE        => $this->message->toArray(),
            self::NODE_ID        => $this->nodeId,
            self::NODE_NAME      => $this->nodeName,
            self::TOPOLOGY_ID    => $this->topologyId,
            self::TOPOLOGY_NAME  => $this->topologyName,
            self::TYPE           => $this->type,
            self::UPDATED        => $this->updated->format(DateTimeUtils::DATE_TIME_UTC),
            self::USER           => $this->getUser(),
        ];
    }

}
