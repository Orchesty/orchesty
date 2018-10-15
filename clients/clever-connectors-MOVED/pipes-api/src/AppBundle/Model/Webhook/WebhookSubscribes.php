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
     * @var array|null
     */
    private $params = [];

    /**
     * @var bool
     */
    private $apiReq = FALSE;

    /**
     * WebhookSubscribes constructor.
     *
     * @param string     $nodeName
     * @param string     $topologyName
     * @param array|null $params
     */
    public function __construct(
        string $nodeName,
        string $topologyName,
        ?array $params = []
    )
    {
        $this->nodeName     = $nodeName;
        $this->topologyName = $topologyName;
        $this->params       = $params;
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