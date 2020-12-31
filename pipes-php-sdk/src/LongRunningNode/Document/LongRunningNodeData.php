<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Exception;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\UpdatedTrait;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\LongRunningNode\Enum\StateEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Utils\Message;

/**
 * Class LongRunningNodeData
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesPhpSdk\LongRunningNode\Repository\LongRunningNodeDataRepository")
 * @ODM\HasLifecycleCallbacks()
 * @ODM\Indexes({
 *     @ODM\Index(keys={"created"="asc"}),
 *     @ODM\Index(keys={"nodeName"="asc"}),
 *     @ODM\Index(keys={"auditLogs"="text"})
 * })
 */
class LongRunningNodeData
{

    use IdTrait;
    use CreatedTrait;
    use UpdatedTrait;

    public const PARENT_PROCESS_HEADER = 'parent-process-id';
    public const UPDATED_BY_HEADER     = 'updated-by';
    public const AUDIT_LOGS_HEADER     = 'audit-logs';
    public const DOCUMENT_ID_HEADER    = 'doc-id';

    public const TOPOLOGY_ID   = 'topologyId';
    public const NODE_ID       = 'nodeId';
    public const NODE_NAME     = 'nodeName';
    public const TOPOLOGY_NAME = 'topologyName';
    public const PROCESS_ID    = 'processId';
    public const STATE         = 'state';
    public const CREATED       = 'created';
    public const UPDATED       = 'updated';
    public const AUDIT_LOGS    = 'auditLogs';
    public const DATA          = 'data';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $parentId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $correlationId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $sequenceId;

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
    private string $nodeId;

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
    private string $nodeName;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", nullable=true)
     */
    private ?string $parentProcess = NULL;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $processId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $state = StateEnum::NEW;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $data;

    /**
     * @var mixed[]|string
     *
     * @ODM\Field(type="string")
     */
    private $headers = [];

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", nullable=true)
     */
    private ?string $updatedBy = NULL;

    /**
     * @var mixed[]|string
     *
     * @ODM\Field(type="string")
     */
    private $auditLogs = [];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $contentType;

    /**
     * LongRunningNodeData constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
        $this->updated = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @param AMQPMessage $message
     *
     * @return LongRunningNodeData
     * @throws Exception
     */
    public static function fromMessage(AMQPMessage $message): LongRunningNodeData
    {
        $headers = Message::getHeaders($message);

        return (new LongRunningNodeData())
            ->setContentType((string) ($headers[PipesHeaders::CONTENT_TYPE] ?? 'application/json'))
            ->setData(Message::getBody($message))
            ->setHeaders($headers)
            ->setParentId(PipesHeaders::get(PipesHeaders::PARENT_ID, $headers) ?? '')
            ->setCorrelationId(PipesHeaders::get(PipesHeaders::CORRELATION_ID, $headers) ?? '')
            ->setTopologyId(PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $headers) ?? '')
            ->setTopologyName(PipesHeaders::get(PipesHeaders::TOPOLOGY_NAME, $headers) ?? '')
            ->setNodeId(PipesHeaders::get(PipesHeaders::NODE_ID, $headers) ?? '')
            ->setNodeName(PipesHeaders::get(PipesHeaders::NODE_NAME, $headers) ?? '')
            ->setParentProcess(PipesHeaders::get(self::PARENT_PROCESS_HEADER, $headers) ?? '')
            ->setProcessId(PipesHeaders::get(PipesHeaders::PROCESS_ID, $headers) ?? '')
            ->setSequenceId(PipesHeaders::get(PipesHeaders::SEQUENCE_ID, $headers) ?? '')
            ->setUpdatedBy(PipesHeaders::get(self::UPDATED_BY_HEADER, $headers) ?? '')
            ->setAuditLogs(Json::decode(PipesHeaders::get(self::AUDIT_LOGS_HEADER, $headers) ?? '{}'));
    }

    /**
     * @return string
     */
    public function getParentId(): string
    {
        return $this->parentId;
    }

    /**
     * @param string $parentId
     *
     * @return LongRunningNodeData
     */
    public function setParentId(string $parentId): LongRunningNodeData
    {
        $this->parentId = $parentId;

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
     * @return LongRunningNodeData
     */
    public function setCorrelationId(string $correlationId): LongRunningNodeData
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSequenceId(): string
    {
        return $this->sequenceId;
    }

    /**
     * @param string $sequenceId
     *
     * @return LongRunningNodeData
     */
    public function setSequenceId(string $sequenceId): LongRunningNodeData
    {
        $this->sequenceId = $sequenceId;

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
     * @return LongRunningNodeData
     */
    public function setTopologyId(string $topologyId): LongRunningNodeData
    {
        $this->topologyId = $topologyId;

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
     * @return LongRunningNodeData
     */
    public function setTopologyName(string $topologyName): LongRunningNodeData
    {
        $this->topologyName = $topologyName;

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
     * @return LongRunningNodeData
     */
    public function setNodeName(string $nodeName): LongRunningNodeData
    {
        $this->nodeName = $nodeName;

        return $this;
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
     * @return LongRunningNodeData
     */
    public function setNodeId(string $nodeId): LongRunningNodeData
    {
        $this->nodeId = $nodeId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getParentProcess(): ?string
    {
        return $this->parentProcess;
    }

    /**
     * @param string|null $parentProcess
     *
     * @return LongRunningNodeData
     */
    public function setParentProcess(?string $parentProcess): LongRunningNodeData
    {
        $this->parentProcess = $parentProcess;

        return $this;
    }

    /**
     * @return string
     */
    public function getProcessId(): string
    {
        return $this->processId;
    }

    /**
     * @param string $processId
     *
     * @return LongRunningNodeData
     */
    public function setProcessId(string $processId): LongRunningNodeData
    {
        $this->processId = $processId;

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return LongRunningNodeData
     */
    public function setState(string $state): LongRunningNodeData
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return LongRunningNodeData
     */
    public function setData(string $data): LongRunningNodeData
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed[]
     * @throws JsonException
     */
    public function getHeaders(): array
    {
        return is_array($this->headers) ? $this->headers : Json::decode($this->headers);
    }

    /**
     * @param mixed[] $headers
     *
     * @return LongRunningNodeData
     */
    public function setHeaders(array $headers): LongRunningNodeData
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    /**
     * @param string|null $updatedBy
     *
     * @return LongRunningNodeData
     */
    public function setUpdatedBy(?string $updatedBy): LongRunningNodeData
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return mixed[]
     * @throws JsonException
     */
    public function getAuditLogs(): array
    {
        return is_array($this->auditLogs) ? $this->auditLogs : Json::decode($this->auditLogs);
    }

    /**
     * @param mixed[] $auditLogs
     *
     * @return LongRunningNodeData
     */
    public function setAuditLogs(array $auditLogs): LongRunningNodeData
    {
        $this->auditLogs = $auditLogs;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     *
     * @return LongRunningNodeData
     */
    public function setContentType(string $contentType): LongRunningNodeData
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @ODM\PreFlush()
     *
     * @throws Exception
     */
    public function preFlush(): void
    {
        if (is_array($this->headers)) {
            $this->headers = Json::encode($this->headers);
        }
        if (is_array($this->auditLogs)) {
            $this->auditLogs = Json::encode($this->auditLogs);
        }
    }

    /**
     * @ODM\PostLoad()
     * @throws JsonException
     */
    public function postLoad(): void
    {
        if (!is_array($this->headers)) {
            $this->headers = Json::decode($this->headers);
        }
        if (!is_array($this->auditLogs)) {
            $this->auditLogs = Json::decode($this->auditLogs);
        }
    }

    /**
     * @return ProcessDto
     * @throws JsonException
     */
    public function toProcessDto(): ProcessDto
    {
        return (new ProcessDto())
            ->setHeaders($this->getHeaders())
            ->setData($this->data);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'topology_id'    => $this->topologyId,
            'topology_name'  => $this->topologyName,
            'node_id'        => $this->nodeId,
            'parent_id'      => $this->parentId,
            'correlation_id' => $this->correlationId,
            'node_name'      => $this->nodeName,
            'parent_process' => $this->parentProcess,
            'process_id'     => $this->processId,
            'sequence_id'    => $this->sequenceId,
            'state'          => $this->state,
            'data'           => $this->data,
            'headers'        => $this->headers,
            'created'        => $this->created->format(DateTimeUtils::DATE_TIME),
            'updated'        => $this->updated->format(DateTimeUtils::DATE_TIME),
            'updated_by'     => $this->updatedBy,
            'audit_logs'     => $this->auditLogs,
            'content_type'   => $this->contentType,
        ];
    }

}
