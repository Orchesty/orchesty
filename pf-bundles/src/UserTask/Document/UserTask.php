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
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\UserTask\Repository\UserTaskRepository", indexes={
 *     @ODM\Index(keys={"node_id"="asc","created"="asc"}),
 *     @ODM\Index(keys={"topology_id"="asc","created"="asc"}),
 *     @ODM\Index(keys={"correlationId"="asc","created"="asc"}),
 *     @ODM\Index(keys={"topology_id"="asc","node_id":"asc","created"="asc"}),
 *     @ODM\Index(keys={"message.body":"text"})
 * })
 * @ODM\HasLifecycleCallbacks()
 */
class UserTask
{

    use IdTrait;
    use CreatedTrait;
    use UpdatedTrait;

    public const ID                 = 'id';
    public const NODE_ID            = 'nodeId';
    public const TOPOLOGY_ID        = 'topologyId';
    public const NODE_NAME          = 'nodeName';
    public const TOPOLOGY_NAME      = 'topologyName';
    public const TOPOLOGY_DESCR     = 'topologyDescr';
    public const TOPOLOGY_VERSION   = 'topologyVersion';
    public const CORRELATION_ID     = 'correlationId';
    public const TYPE               = 'type';
    public const RETURN_EXCHANGE    = 'returnExchange';
    public const RETURN_ROUTING_KEY = 'returnRoutingKey';
    public const MESSAGE            = 'message';
    public const AUDIT_LOGS         = 'auditLogs';
    public const CREATED            = 'created';
    public const UPDATED            = 'updated';
    public const USER               = 'user';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $nodeId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $topologyId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $nodeName;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $topologyName;

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
    private string $returnExchange;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $returnRoutingKey;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $correlationId;

    /**
     * @var UserTaskMessage
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage")
     */
    private UserTaskMessage $message;

    /**
     * @var mixed[]|string
     *
     * @ODM\Field(type="string")
     */
    private array|string $auditLogs = [];

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
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
    public function setNodeId(string $nodeId): UserTask
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
    public function setTopologyId(string $topologyId): UserTask
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
    public function setNodeName(string $nodeName): UserTask
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
    public function setTopologyName(string $topologyName): UserTask
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
    public function setType(string $type): UserTask
    {
        $this->type = UserTaskEnum::isValid($type);

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
    public function setReturnExchange(string $returnExchange): UserTask
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
    public function setReturnRoutingKey(string $returnRoutingKey): UserTask
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
    public function setCorrelationId(string $correlationId): UserTask
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
    public function setMessage(UserTaskMessage $message): UserTask
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
    public function setAuditLogs(array|string $auditLogs): UserTask
    {
        $this->auditLogs = $auditLogs;

        return $this;
    }

    /**
     * @param mixed[] $data
     *
     * @return UserTask
     */
    public function addAuditLog(array $data): UserTask
    {
        /** @var mixed[] $arr */
        $arr             = $this->getAuditLogs(); // Ensure array should someone call flush() before this
        $arr[]           = $data;
        $this->auditLogs = $arr;

        return $this;
    }

    /**
     * @ODM\PreFlush()
     *
     * @throws Exception
     */
    public function preFlush(): void
    {
        if (is_array($this->auditLogs)) {
            $this->auditLogs = Json::encode($this->auditLogs);
        }
    }

    /**
     * @ODM\PostLoad()
     */
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
            self::ID             => $this->id,
            self::NODE_ID        => $this->nodeId,
            self::NODE_NAME      => $this->nodeName,
            self::TOPOLOGY_ID    => $this->topologyId,
            self::TOPOLOGY_NAME  => $this->topologyName,
            self::TYPE           => $this->type,
            self::CORRELATION_ID => $this->correlationId,
            self::CREATED        => $this->created->format(DateTimeUtils::DATE_TIME_UTC),
            self::UPDATED        => $this->updated->format(DateTimeUtils::DATE_TIME_UTC),
            self::MESSAGE        => $this->message->toArray(),
            self::AUDIT_LOGS     => $this->getAuditLogs(),
            self::USER           => $this->getUser(),
        ];
    }

}
