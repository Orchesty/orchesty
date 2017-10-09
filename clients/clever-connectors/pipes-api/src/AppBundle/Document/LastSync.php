<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class LastSync
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\LastSyncRepository")
 */
class LastSync
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    protected $user;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $topologyName;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $nodeName;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    protected $timestamp;

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return LastSync
     */
    public function setUser(string $user): LastSync
    {
        $this->user = $user;

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
     * @return LastSync
     */
    public function setTopologyName(string $topologyName): LastSync
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
     * @return LastSync
     */
    public function setNodeName(string $nodeName): LastSync
    {
        $this->nodeName = $nodeName;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getTimestamp(): ?DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param DateTime $timestamp
     *
     * @return LastSync
     */
    public function setTimestamp(DateTime $timestamp): LastSync
    {
        $this->timestamp = $timestamp;

        return $this;
    }

}