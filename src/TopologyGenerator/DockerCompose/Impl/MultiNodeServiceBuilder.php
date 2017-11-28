<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
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
     * @var Topology
     */
    private $topology;

    /**
     * NodeServiceBuilder constructor.
     *
     * @param Topology             $topology
     * @param Environment          $environment
     * @param string               $registry
     * @param string               $network
     * @param VolumePathDefinition $volumePathDefinition
     */
    public function __construct(
        Topology $topology,
        Environment $environment,
        string $registry,
        string $network,
        VolumePathDefinition $volumePathDefinition
    )
    {
        $this->topology = $topology;
        parent::__construct($environment, $registry, $network, $volumePathDefinition);
    }

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $service = new Service(sprintf('%s_multi_bridge', $this->topology->getId()));
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->addEnvironment(Environment::RABBITMQ_HOST, $this->environment->getRabbitMqHost())
            ->addEnvironment(Environment::RABBITMQ_PORT, $this->environment->getRabbitMqPort())
            ->addEnvironment(Environment::RABBITMQ_USER, $this->environment->getRabbitMqUser())
            ->addEnvironment(Environment::RABBITMQ_PASS, $this->environment->getRabbitMqPass())
            ->addEnvironment(Environment::RABBITMQ_VHOST, $this->environment->getRabbitMqVHost())
            ->addVolume($this->volumePathDefinition->getSourceVolume('topology.json') . ':/srv/app/topology/topology.json')
            ->setCommand('./dist/src/bin/pipes.js start all_nodes')
            ->addNetwork($this->network);

        return $service;
    }

}