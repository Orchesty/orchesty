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
    private $subscribeUrl;

    /**
     * @var string
     */
    private $unSubscribeUrl;

    /**
     * @var bool
     */
    private $apiReq = FALSE;

    /**
     * WebhookSubscribes constructor.
     *
     * @param string     $nodeName
     * @param string     $topologyName
     * @param string     $subscribeUrl
     * @param string     $unSubscribeUrl
     * @param array|null $params
     */
    public function __construct(
        string $nodeName,
        string $topologyName,
        string $subscribeUrl,
        string $unSubscribeUrl,
        ?array $params = []
    )
    {
        $this->nodeName       = $nodeName;
        $this->topologyName   = $topologyName;
        $this->subscribeUrl   = $subscribeUrl;
        $this->unSubscribeUrl = $unSubscribeUrl;
        $this->params         = $params;
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
    public function getSubscribeUrl(): string
    {
        return $this->subscribeUrl;
    }

    /**
     * @param string $subscribeUrl
     *
     * @return WebhookSubscribes
     */
    public function setSubscribeUrl(string $subscribeUrl): WebhookSubscribes
    {
        $this->subscribeUrl = $subscribeUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnSubscribeUrl(): string
    {
        return $this->unSubscribeUrl;
    }

    /**
     * @param string $unSubscribeUrl
     *
     * @return WebhookSubscribes
     */
    public function setUnSubscribeUrl(string $unSubscribeUrl): WebhookSubscribes
    {
        $this->unSubscribeUrl = $unSubscribeUrl;

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

    /**
     * @param array $params
     *
     * @return WebhookSubscribes
     */
    public function setParams(array $params): WebhookSubscribes
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

}