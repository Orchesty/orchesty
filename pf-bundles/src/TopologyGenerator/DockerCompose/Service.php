<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 11:53 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Directives\Configs;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;

/**
 * Class Service
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose
 */
class Service
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $image = '';

    /**
     * @var string
     */
    protected $workDir = '';

    /**
     * @var string
     */
    protected $user = '';

    /**
     * @var array
     */
    protected $environments = [];

    /**
     * @var array
     */
    protected $volumes = [];

    /**
     * @var array
     */
    protected $ports = [];

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @var array
     */
    protected $networks = [];

    /**
     * @var array
     */
    protected $dependsOn = [];

    /**
     * @var array
     */
    protected $configs = [];

    /**
     * Service constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = GeneratorUtils::createServiceName($name);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     *
     * @return Service
     */
    public function setImage(string $image): Service
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return string
     */
    public function getWorkDir(): string
    {
        return $this->workDir;
    }

    /**
     * @param string $workDir
     *
     * @return Service
     */
    public function setWorkDir(string $workDir): Service
    {
        $this->workDir = $workDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return Service
     */
    public function setUser(string $user): Service
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return array
     */
    public function getEnvironments(): array
    {
        return $this->environments;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return Service
     */
    public function addEnvironment(string $key, string $value): Service
    {
        $this->environments[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getVolumes(): array
    {
        return $this->volumes;
    }

    /**
     * @param string $volume
     *
     * @return Service
     */
    public function addVolume(string $volume): Service
    {
        $this->volumes[] = $volume;

        return $this;
    }

    /**
     * @return array
     */
    public function getPorts(): array
    {
        return $this->ports;
    }

    /**
     * @param string $port
     *
     * @return Service
     */
    public function addPort(string $port): Service
    {
        $this->ports[] = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     *
     * @return Service
     */
    public function setCommand(string $command): Service
    {
        $this->command = $command;

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
     * @return Service
     */
    public function addNetwork(string $network): Service
    {
        $this->networks[] = $network;

        return $this;
    }

    /**
     * @return array
     */
    public function getDependsOn(): array
    {
        return $this->dependsOn;
    }

    /**
     * @param string $dependsOn
     *
     * @return Service
     */
    public function addDependOn(string $dependsOn): Service
    {
        $this->dependsOn[] = $dependsOn;

        return $this;
    }

    /**
     * @return array
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

    /**
     * @param Configs $configs
     *
     * @return Service
     */
    public function addConfigs(Configs $configs): Service
    {
        $this->configs[] = $configs;

        return $this;
    }

}
