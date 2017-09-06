<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 8:56 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\ServiceBuilderInterface;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;

/**
 * Class ProbeServiceBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
class ProbeServiceBuilder implements ServiceBuilderInterface
{

    private const IMAGE = 'pf-typescript:dev';

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
     * NodeServiceBuilder constructor.
     *
     * @param Environment $environment
     * @param string      $registry
     * @param string      $network
     */
    public function __construct(Environment $environment, string $registry, string $network)
    {
        $this->environment = $environment;
        $this->registry    = $registry;
        $this->network     = $network;
    }

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $service = new Service('probe');
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->addEnvironment(Environment::RABBITMQ_HOST, $this->environment->getRabbitMqHost())
            ->addEnvironment(Environment::RABBITMQ_PORT, $this->environment->getRabbitMqPort())
            ->addEnvironment(Environment::RABBITMQ_USER, $this->environment->getRabbitMqUser())
            ->addEnvironment(Environment::RABBITMQ_PASS, $this->environment->getRabbitMqPass())
            ->addEnvironment(Environment::RABBITMQ_VHOST, $this->environment->getRabbitMqVHost())
            ->addVolume('./topology.json:/srv/app/topology.json')
            ->addPort('8007:8007')
            ->setCommand('./dist/src/bin/pipes.js start probe')
            ->addNetwork($this->network);

        return $service;
    }

}