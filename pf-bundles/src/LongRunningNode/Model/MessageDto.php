<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;

/**
 * Class MessageDto
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Model
 */
class MessageDto
{

    /**
     * @var string|null
     */
    private $docId;

    /**
     * @var string
     */
    private $data;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $nodeId;

    /**
     * @var string
     */
    private $topologyId;

    /**
     * @var string
     */
    private $processId;

    /**
     * @var string
     */
    private $parentProcess;

    /**
     * @var string|null
     */
    private $updatedBy;

    /**
     * MessageDto constructor.
     *
     * @param string $data
     * @param array  $headers
     */
    public function __construct(string $data, array $headers)
    {
        $this->data          = $data;
        $this->headers       = $headers;
        $this->nodeId        = PipesHeaders::get(PipesHeaders::NODE_ID, $headers);
        $this->topologyId    = PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $headers);
        $this->processId     = PipesHeaders::get(PipesHeaders::PROCESS_ID, $headers);
        $this->docId         = PipesHeaders::get(LongRunningNodeData::DOCUMENT_ID_HEADER, $headers);
        $this->parentProcess = PipesHeaders::get(LongRunningNodeData::PARENT_PROCESS_HEADER, $headers);
        $this->updatedBy     = PipesHeaders::get(LongRunningNodeData::UPDATED_BY_HEADER, $headers);
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    /**
     * @return string
     */
    public function getTopologyId(): string
    {
        return $this->topologyId;
    }

    /**
     * @return string
     */
    public function getProcessId(): string
    {
        return $this->processId;
    }

    /**
     * @return string|null
     */
    public function getDocId(): ?string
    {
        return $this->docId;
    }

    /**
     * @return string|null
     */
    public function getParentProcess(): ?string
    {
        return $this->parentProcess;
    }

    /**
     * @return null|string
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

}