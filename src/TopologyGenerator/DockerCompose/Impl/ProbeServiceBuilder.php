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
 * Class ProbeServiceBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
class ProbeServiceBuilder implements ServiceBuilderInterface
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
     * @var Topology
     */
    private $topology;

    /**
     * @var VolumePathDefinition
     */
    private $volumePathDefinition;

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
        $this->topology             = $topology;
        $this->volumePathDefinition = $volumePathDefinition;
    }

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $service = new Service(sprintf('%s_probe', $this->topology->getId()));
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->addEnvironment(Environment::RABBITMQ_HOST, $this->environment->getRabbitMqHost())
            ->addEnvironment(Environment::RABBITMQ_PORT, $this->environment->getRabbitMqPort())
            ->addEnvironment(Environment::RABBITMQ_USER, $this->environment->getRabbitMqUser())
            ->addEnvironment(Environment::RABBITMQ_PASS, $this->environment->getRabbitMqPass())
            ->addEnvironment(Environment::RABBITMQ_VHOST, $this->environment->getRabbitMqVHost())
            //->addPort('${DEV_IP}:8007:8007')
            ->addVolume($this->volumePathDefinition->getSourceVolume('topology.json') . ':/srv/app/topology.json')
            ->setCommand('./dist/src/bin/pipes.js start probe')
            ->addNetwork($this->network);

        return $service;
    }

}