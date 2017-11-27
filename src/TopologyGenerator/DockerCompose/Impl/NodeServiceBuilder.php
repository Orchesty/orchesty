<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 3:17 PM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\ServiceBuilderInterface;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinition;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;

/**
 * Class NodeServiceBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
class NodeServiceBuilder implements ServiceBuilderInterface
{

    protected const IMAGE = 'pf-bridge:dev';

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var string
     */
    protected $registry;

    /**
     * @var string
     */
    protected $network;

    /**
     * @var VolumePathDefinition
     */
    protected $volumePathDefinition;

    /**
     * NodeServiceBuilder constructor.
     *
     * @param Environment          $environment
     * @param string               $registry
     * @param string               $network
     * @param VolumePathDefinition $volumePathDefinition
     */
    public function __construct(
        Environment $environment,
        string $registry,
        string $network,
        VolumePathDefinition $volumePathDefinition
    )
    {
        $this->environment          = $environment;
        $this->registry             = $registry;
        $this->network              = $network;
        $this->volumePathDefinition = $volumePathDefinition;
    }

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $service = new Service(GeneratorUtils::normalizeName($node->getId(), $node->getName()));
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->addEnvironment(Environment::RABBITMQ_HOST, $this->environment->getRabbitMqHost())
            ->addEnvironment(Environment::RABBITMQ_PORT, $this->environment->getRabbitMqPort())
            ->addEnvironment(Environment::RABBITMQ_USER, $this->environment->getRabbitMqUser())
            ->addEnvironment(Environment::RABBITMQ_PASS, $this->environment->getRabbitMqPass())
            ->addEnvironment(Environment::RABBITMQ_VHOST, $this->environment->getRabbitMqVHost())
            ->addVolume($this->volumePathDefinition->getSourceVolume('topology.json') . ':/srv/app/topology/topology.json')
            ->setCommand(sprintf(
                    './dist/src/bin/pipes.js start node --id %s',
                    GeneratorUtils::createServiceName(GeneratorUtils::normalizeName($node->getId(), $node->getName()))
                )
            )
            ->addNetwork($this->network);

        return $service;
    }

}