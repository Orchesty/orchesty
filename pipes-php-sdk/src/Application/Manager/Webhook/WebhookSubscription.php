<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Manager\Webhook;

/**
 * Class WebhookSubscription
 *
 * @package Hanaboso\PipesPhpSdk\Application\Manager\Webhook
 */
final class WebhookSubscription
{

    public const NAME     = 'name';
    public const TOPOLOGY = 'topology';

    /**
     * WebhookSubscription constructor.
     *
     * @param string  $name
     * @param string  $node
     * @param string  $topology
     * @param mixed[] $parameters
     */
    public function __construct(
        private string $name,
        private string $node,
        private string $topology,
        private array $parameters = [],
    )
    {
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
