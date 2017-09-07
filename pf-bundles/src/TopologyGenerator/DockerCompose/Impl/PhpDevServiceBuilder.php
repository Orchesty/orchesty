<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 7.9.17
 * Time: 15:22
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\ServiceBuilderInterface;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;

class PhpDevServiceBuilder implements ServiceBuilderInterface
{

    private const IMAGE = 'php-dev:dev';

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
        $service = new Service('app');
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->setUser('${DEV_UID}:${DEV_GID}')
            ->setWorkDir('/var/www/pipes/client/demo/mapper')
            ->addEnvironment(Environment::DEV_UID, $this->environment->getDevUid())
            ->addEnvironment(Environment::DEV_GID, $this->environment->getDevGid())
            ->addVolume('../../:/var/www/pipes')
            ->addVolume('${SSH_AUTH_SOCK}:/ssh-agent')
            ->addNetwork($this->network);

        return $service;
    }

}
