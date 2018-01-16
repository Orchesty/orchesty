<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 11:48 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Directives\Configs;

/**
 * Class Compose
 *
 * @package Hanaboso\PipesFramevork\TopologyGenerator\DockerCompose
 */
class Compose
{

    /**
     * @var string
     */
    protected $version = '3.3';

    /**
     * @var Service[]
     */
    protected $services = [];

    /**
     * @var array
     */
    protected $networks = [];

    /**
     * @var array
     */
    protected $configs = [];

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     *
     * @return Compose
     */
    public function setVersion(string $version): Compose
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return Service[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @param Service $service
     *
     * @return Compose
     */
    public function addService(Service $service): Compose
    {
        $this->services[$service->getName()] = $service;

        return $this;
    }

    /**
     * @return array
     */
    public function getNetworks(): array
    {
        return $this->networks;
    }

    /**
     * @param string $network
     *
     * @return Compose
     */
    public function addNetwork(string $network): Compose
    {
        $this->networks[] = $network;

        return $this;
    }

    /**
     * @param string $topologyprefix
     * @param bool   $external
     *
     * @return Compose
     */
    public function addConfigs(string $topologyprefix, bool $external): Compose
    {
        $this->configs[] = new Configs($topologyprefix, NULL, $external);

        return $this;
    }

    /**
     * @return array
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

}
