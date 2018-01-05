<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 8:56 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\ServiceBuilderInterface;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinition;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;

/**
 * Class CounterServiceBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
class CounterServiceBuilder implements ServiceBuilderInterface
{

    private const IMAGE = 'pf-bridge:dev';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $registry;

    /**
     * @var string
     */
    private $network;

    /**
     * @var VolumePathDefinition
     */
    private $volumePathDefinition;

    /**
     * @var Topology
     */
    private $topology;

    /**
     * NodeServiceBuilder constructor.
     *
     * @param Environment          $environment
     * @param string               $registry
     * @param string               $network
     * @param Topology             $topology
     * @param VolumePathDefinition $volumePathDefinition
     */
    public function __construct(
        Environment $environment,
        string $registry,
        string $network,
        Topology $topology,
        VolumePathDefinition $volumePathDefinition
    )
    {
        $this->environment          = $environment;
        $this->registry             = $registry;
        $this->network              = $network;
        $this->volumePathDefinition = $volumePathDefinition;
        $this->topology             = $topology;
    }

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $service = new Service(sprintf('%s_counter', $this->topology->getId()));
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->addEnvironment(Environment::RABBITMQ_HOST, $this->environment->getRabbitMqHost())
            ->addEnvironment(Environment::RABBITMQ_PORT, $this->environment->getRabbitMqPort())
            ->addEnvironment(Environment::RABBITMQ_USER, $this->environment->getRabbitMqUser())
            ->addEnvironment(Environment::RABBITMQ_PASS, $this->environment->getRabbitMqPass())
            ->addEnvironment(Environment::RABBITMQ_VHOST, $this->environment->getRabbitMqVHost())
            ->addVolume($this->volumePathDefinition->getSourceVolume('topology.json') . ':/srv/app/topology/topology.json')
            ->setCommand('./dist/src/bin/pipes.js start counter')
            ->addNetwork($this->network);

        return $service;
    }

}
