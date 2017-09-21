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
    protected $topology;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $node;

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
    public function getTopology(): string
    {
        return $this->topology;
    }

    /**
     * @param string $topology
     *
     * @return LastSync
     */
    public function setTopology(string $topology): LastSync
    {
        $this->topology = $topology;

        return $this;
    }

    /**
     * @return string
     */
    public function getNode(): string
    {
        return $this->node;
    }

    /**
     * @param string $node
     *
     * @return LastSync
     */
    public function setNode(string $node): LastSync
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getTimestamp(): DateTime
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