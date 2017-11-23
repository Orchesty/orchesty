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

    use ServiceTrait;

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
     * @var string
     */
    private $topologyPrefix;

    /**
     * @var string
     */
    private $topologyMode;

    /**
     * NodeServiceBuilder constructor.
     *
     * @param Environment          $environment
     * @param string               $registry
     * @param string               $network
     * @param Topology             $topology
     * @param VolumePathDefinition $volumePathDefinition
     * @param string               $topologyPrefix
     * @param string               $topologyMode
     */
    public function __construct(
        Environment $environment,
        string $registry,
        string $network,
        Topology $topology,
        VolumePathDefinition $volumePathDefinition,
        string $topologyPrefix,
        string $topologyMode
    )
    {
        $this->environment          = $environment;
        $this->registry             = $registry;
        $this->network              = $network;
        $this->volumePathDefinition = $volumePathDefinition;
        $this->topology             = $topology;
        $this->topologyPrefix       = $topologyPrefix;
        $this->topologyMode         = $topologyMode;
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
            //            ->addConfigs(new Configs($this->topologyprefix, '/srv/app/topology/topology.json'))
            //            ->addVolume($this->volumePathDefinition->getSourceVolume('topology.json') . ':/srv/app/topology/topology.json')
            ->setCommand('./dist/src/bin/pipes.js start counter')
            ->addNetwork($this->network);

        $this->addServiceEnvironment($service, $this->topologyMode, $this->topologyPrefix,
            $this->volumePathDefinition->getSourceVolume('topology.json'));

        return $service;
    }

    /**
     * @return VolumePathDefinition
     */
    public function getVolumePathDefinition(): VolumePathDefinition
    {
        return $this->volumePathDefinition;
    }

}
