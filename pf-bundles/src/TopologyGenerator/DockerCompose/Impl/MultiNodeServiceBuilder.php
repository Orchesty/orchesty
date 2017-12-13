<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinition;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;

/**
 * Class MultiNodeServiceBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
class MultiNodeServiceBuilder extends NodeServiceBuilder
{

    /**
     * @var string
     */
    private $nodeName;

    /**
     * NodeServiceBuilder constructor.
     *
     * @param string               $nodeName
     * @param Environment          $environment
     * @param string               $registry
     * @param string               $network
     * @param VolumePathDefinition $volumePathDefinition
     */
    public function __construct(
        string $nodeName,
        Environment $environment,
        string $registry,
        string $network,
        VolumePathDefinition $volumePathDefinition
    )
    {
        $this->nodeName = $nodeName;
        parent::__construct($environment, $registry, $network, $volumePathDefinition);
    }

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $service = new Service($this->nodeName);
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->addEnvironment(Environment::RABBITMQ_HOST, $this->environment->getRabbitMqHost())
            ->addEnvironment(Environment::RABBITMQ_PORT, $this->environment->getRabbitMqPort())
            ->addEnvironment(Environment::RABBITMQ_USER, $this->environment->getRabbitMqUser())
            ->addEnvironment(Environment::RABBITMQ_PASS, $this->environment->getRabbitMqPass())
            ->addEnvironment(Environment::RABBITMQ_VHOST, $this->environment->getRabbitMqVHost())
            ->addEnvironment(Environment::MULTI_PROBE_HOST, $this->environment->getMultiProbeHost())
            ->addEnvironment(Environment::MULTI_PROBE_PORT, $this->environment->getMultiProbePort())
            ->addVolume($this->volumePathDefinition->getSourceVolume('topology.json') . ':/srv/app/topology/topology.json')
            ->setCommand('./dist/src/bin/pipes.js start multi_bridge')
            ->addNetwork($this->network);

        return $service;
    }

}
