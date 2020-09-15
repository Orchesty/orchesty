<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class NodeProgress
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 * @ODM\EmbeddedDocument
 */
class NodeProgress
{

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
    private string $nodeId;

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
    private string $status;

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
     * @return NodeProgress
     */
    public function setProcessId(string $processId): NodeProgress
    {
        $this->processId = $processId;

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
     * @return NodeProgress
     */
    public function setNodeId(string $nodeId): NodeProgress
    {
        $this->nodeId = $nodeId;

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
     * @return NodeProgress
     */
    public function setNodeName(string $nodeName): NodeProgress
    {
        $this->nodeName = $nodeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return NodeProgress
     */
    public function setStatus(string $status): NodeProgress
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return [
            'process_id' => $this->processId,
            'id'         => $this->nodeId,
            'name'       => $this->nodeName,
            'status'     => $this->status,
        ];
    }

}
