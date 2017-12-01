<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinition;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;

/**
 * Class MultiNodeServiceBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
class MultiNodeServiceBuilder extends NodeServiceBuilder
{

    use ServiceTrait;

    /**
     * @var string
     */
    protected $topology;

    /**
     * NodeServiceBuilder constructor.
     *
     * @param string               $nodeName
     * @param Environment          $environment
     * @param string               $registry
     * @param string               $network
     * @param VolumePathDefinition $volumePathDefinition
     * @param string               $topologyPrefix
     * @param string               $topologyMode
     */
    public function __construct(
        string $nodeName,
        Environment $environment,
        string $registry,
        string $network,
        VolumePathDefinition $volumePathDefinition,
        string $topologyPrefix,
        string $topologyMode
    )
    {
        parent::__construct($environment, $registry, $network, $volumePathDefinition, $topologyPrefix, $topologyMode);
        $this->nodeName = $nodeName;
        $this->topology = $topology;
    }

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $name = GeneratorUtils::createServiceName(sprintf('%s-multi-bridge', $this->topology->getId()));
        $service = new Service($name);
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->addEnvironment(Environment::RABBITMQ_HOST, $this->environment->getRabbitMqHost())
            ->addEnvironment(Environment::RABBITMQ_PORT, $this->environment->getRabbitMqPort())
            ->addEnvironment(Environment::RABBITMQ_USER, $this->environment->getRabbitMqUser())
            ->addEnvironment(Environment::RABBITMQ_PASS, $this->environment->getRabbitMqPass())
            ->addEnvironment(Environment::RABBITMQ_VHOST, $this->environment->getRabbitMqVHost())
            ->addEnvironment(Environment::MULTI_PROBE_HOST, $this->environment->getMultiProbeHost())
            ->addEnvironment(Environment::MULTI_PROBE_PORT, $this->environment->getMultiProbePort())
            ->setCommand('./dist/src/bin/pipes.js start multi_bridge')
            ->addNetwork($this->network);

        $this->addServiceEnvironment($service, $this->topologyMode, $this->topologyPrefix,
            $this->volumePathDefinition->getSourceVolume('topology.json'));

        return $service;
    }

}
