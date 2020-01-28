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
    private string $name;

    /**
     * @var string
     */
    private string $node;

    /**
     * @var string
     */
    private string $topology;

    /**
     * @var mixed[]
     */
    private array $parameters;

    /**
     * WebhookSubscription constructor.
     *
     * @param string  $name
     * @param string  $node
     * @param string  $topology
     * @param mixed[] $parameters
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
     * @return mixed[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

}
