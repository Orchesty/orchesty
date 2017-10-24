<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Webhook;

/**
 * Class WebhookSubscribes
 *
 * @package CleverConnectors\AppBundle\Model\Webhook
 */
class WebhookSubscribes
{

    /**
     * @var string
     */
    private $nodeName;

    /**
     * @var string
     */
    private $topologyName;

    /**
     * @var string
     */
    private $registrationUrl;

    /**
     * @var string
     */
    private $unregistrationUrl;

    /**
     * @var bool
     */
    private $apiReq = FALSE;

    /**
     * WebhookSubscribes constructor.
     *
     * @param string $nodeName
     * @param string $topologyName
     * @param string $registrationUrl
     * @param string $unregistrationUrl
     */
    public function __construct($nodeName, $topologyName, $registrationUrl, $unregistrationUrl)
    {
        $this->nodeName          = $nodeName;
        $this->topologyName      = $topologyName;
        $this->registrationUrl   = $registrationUrl;
        $this->unregistrationUrl = $unregistrationUrl;
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
     * @return WebhookSubscribes
     */
    public function setNodeName(string $nodeName): WebhookSubscribes
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
     * @return WebhookSubscribes
     */
    public function setTopologyName(string $topologyName): WebhookSubscribes
    {
        $this->topologyName = $topologyName;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegistrationUrl(): string
    {
        return $this->registrationUrl;
    }

    /**
     * @param string $registrationUrl
     *
     * @return WebhookSubscribes
     */
    public function setRegistrationUrl(string $registrationUrl): WebhookSubscribes
    {
        $this->registrationUrl = $registrationUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnregistrationUrl(): string
    {
        return $this->unregistrationUrl;
    }

    /**
     * @param string $unregistrationUrl
     *
     * @return WebhookSubscribes
     */
    public function setUnregistrationUrl(string $unregistrationUrl): WebhookSubscribes
    {
        $this->unregistrationUrl = $unregistrationUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isApiReq(): bool
    {
        return $this->apiReq;
    }

    /**
     * @param bool $apiReq
     *
     * @return WebhookSubscribes
     */
    public function setApiReq(bool $apiReq): WebhookSubscribes
    {
        $this->apiReq = $apiReq;

        return $this;
    }

}