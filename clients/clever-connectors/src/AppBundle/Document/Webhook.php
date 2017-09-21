<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Webhook
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\WebhookRepository")
 */
class Webhook
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    private $user;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $systemKey;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $topologyName;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $nodeName;

    /**
     * @var boolean
     *
     * @ODM\Field(type="boolean")
     */
    private $unsubscribeFailed;

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
     * @return Webhook
     */
    public function setUser(string $user): Webhook
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getSystemKey(): string
    {
        return $this->systemKey;
    }

    /**
     * @param string $systemKey
     *
     * @return Webhook
     */
    public function setSystemKey(string $systemKey): Webhook
    {
        $this->systemKey = $systemKey;

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
     * @return Webhook
     */
    public function setTopologyName(string $topologyName): Webhook
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
     * @return Webhook
     */
    public function setNodeName(string $nodeName): Webhook
    {
        $this->nodeName = $nodeName;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUnsubscribeFailed(): bool
    {
        return $this->unsubscribeFailed;
    }

    /**
     * @param bool $unsubscribeFailed
     *
     * @return Webhook
     */
    public function setUnsubscribeFailed(bool $unsubscribeFailed): Webhook
    {
        $this->unsubscribeFailed = $unsubscribeFailed;

        return $this;
    }

}