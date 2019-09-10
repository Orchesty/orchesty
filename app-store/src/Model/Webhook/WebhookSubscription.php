<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Model\Webhook;

/**
 * Class WebhookSubscription
 *
 * @package Hanaboso\HbPFAppStore\Model\Webhook
 */
final class WebhookSubscription
{

    public const NAME     = 'name';
    public const TOPOLOGY = 'topology';

    /**
     * @var string
     */
    private $name;

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
     * @param string $name
     * @param string $node
     * @param string $topology
     * @param array  $parameters
     */
    public function __construct(string $name, string $node, string $topology, array $parameters = [])
    {
        $this->name       = $name;
        $this->node       = $node;
        $this->topology   = $topology;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
