<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Document;

use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;

/**
 * Class LongRunningNodeData
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\LongRunningNode\Repository\LongRunningNodeDataRepository")
 *
 * @ODM\HasLifecycleCallbacks()
 */
class LongRunningNodeData
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $topologyId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $nodeId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $parentProcess;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $processId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $state;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $data;

    /**
     * @var array|string
     *
     * @ODM\Field(type="string")
     */
    private $headers = [];

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $created;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $updated;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $updatedBy;

    /**
     * @var array|string
     *
     * @ODM\Field(type="string")
     */
    private $auditLogs = [];

    /**
     * LongRunningNodeData constructor.
     */
    public function __construct()
    {
        $this->created = new DateTime('now', new DateTimeZone('UTC'));
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
     * @return string
     */
    public function getParentProcess(): string
    {
        return $this->parentProcess;
    }

    /**
     * @param string $parentProcess
     *
     * @return LongRunningNodeData
     */
    public function setParentProcess(string $parentProcess): LongRunningNodeData
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
     * @return array
     */
    public function getHeaders(): array
    {
        return is_array($this->headers) ? $this->headers : json_decode($this->headers, TRUE);
    }

    /**
     * @param array $headers
     *
     * @return LongRunningNodeData
     */
    public function setHeaders(array $headers): LongRunningNodeData
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     *
     * @return LongRunningNodeData
     */
    public function setCreated(DateTime $created): LongRunningNodeData
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * @param DateTime $updated
     *
     * @return LongRunningNodeData
     */
    public function setUpdated(DateTime $updated): LongRunningNodeData
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedBy(): string
    {
        return $this->updatedBy;
    }

    /**
     * @param string $updatedBy
     *
     * @return LongRunningNodeData
     */
    public function setUpdatedBy(string $updatedBy): LongRunningNodeData
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return array
     */
    public function getAuditLogs(): array
    {
        return is_array($this->auditLogs) ? $this->auditLogs : json_decode($this->auditLogs, TRUE);
    }

    /**
     * @param array $auditLogs
     *
     * @return LongRunningNodeData
     */
    public function setAuditLogs(array $auditLogs): LongRunningNodeData
    {
        $this->auditLogs = $auditLogs;

        return $this;
    }

    /**
     * @ODM\PreFlush()
     */
    public function preFlush(): void
    {
        if (is_array($this->headers)) {
            $this->headers = json_encode($this->headers);
        }
        if (is_array($this->auditLogs)) {
            $this->auditLogs = json_encode($this->auditLogs);
        }
        $this->updated = new DateTime('now', new DateTimeZone('UTC'));
    }

    /**
     * @ODM\PostLoad()
     */
    public function postLoad(): void
    {
        if (!is_array($this->headers)) {
            $this->headers = json_decode($this->headers, TRUE);
        }
        if (!is_array($this->auditLogs)) {
            $this->auditLogs = json_decode($this->auditLogs, TRUE);
        }
    }

    /**
     * @return ProcessDto
     */
    public function toProcessDto(): ProcessDto
    {
        return (new ProcessDto())
            ->setHeaders($this->getHeaders())
            ->setData($this->data);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return LongRunningNodeData
     */
    public static function fromProcessDto(ProcessDto $dto): LongRunningNodeData
    {
        $ent = new LongRunningNodeData();
        $ent->setData($dto->getData())
            ->setHeaders($dto->getHeaders())
            ->setTopologyId((string) $dto->getHeader(PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID), ''))
            ->setNodeId((string) $dto->getHeader(PipesHeaders::createKey(PipesHeaders::NODE_ID), ''))
            ->setParentProcess((string) $dto->getHeader(PipesHeaders::createKey('parent-process-id'), ''))
            ->setProcessId((string) $dto->getHeader(PipesHeaders::createKey(PipesHeaders::PROCESS_ID), ''))
            ->setCreated(
                new DateTime((string) $dto->getHeader(PipesHeaders::createKey('created'), ''), new DateTimeZone('UTC')))
            ->setUpdated(
                new DateTime((string) $dto->getHeader(PipesHeaders::createKey('updated'), ''), new DateTimeZone('UTC')))
            ->setUpdatedBy((string) $dto->getHeader(PipesHeaders::createKey('updated-by'), ''))
            ->setAuditLogs(json_decode($dto->getHeader(PipesHeaders::createKey('audit-logs'), '{}'), TRUE));

        return $ent;
    }

}