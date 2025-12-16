<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class TagsCamelCase
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\EmbeddedDocument]
class TagsCamelCase
{

    /**
     * @var string
     */
    #[ODM\Field]
    private string $userId;

    /**
     * @var string
     */
    #[ODM\Field]
    private string $nodeId;

    /**
     * @var string
     */
    #[ODM\Field]
    private string $topologyId;

    /**
     * @var string
     */
    #[ODM\Field]
    private string $applicationId;

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return self
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

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
     * @return self
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
     * @return self
     */
    public function setTopologyId(string $topologyId): self
    {
        $this->topologyId = $topologyId;

        return $this;
    }

    /**
     * @return string
     */
    public function getApplicationId(): string
    {
        return $this->applicationId;
    }

    /**
     * @param string $applicationId
     * @return self
     */
    public function setApplicationId(string $applicationId): self
    {
        $this->applicationId = $applicationId;

        return $this;
    }

}
