<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Model\Webhook;

/**
 * Class WebhookSubscription
 *
 * @package Hanaboso\PipesFramework\Application\Model\Webhook
 */
final class WebhookSubscription
{

    /**
     * @var string
     */
    private $node;

    /**
     * @var string
     */
    private $topology;

    /**
     * @var array
     */
    private $parameters;

    /**
     * WebhookSubscription constructor.
     *
     * @param string $node
     * @param string $topology
     * @param array  $parameters
     */
    public function __construct(string $node, string $topology, array $parameters = [])
    {
        $this->node       = $node;
        $this->topology   = $topology;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getNode(): string
    {
        return $this->node;
    }

    /**
     * @return string
     */
    public function getTopology(): string
    {
        return $this->topology;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

}
