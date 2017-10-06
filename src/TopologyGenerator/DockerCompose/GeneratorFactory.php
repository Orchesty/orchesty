<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 8.9.17
 * Time: 11:16
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\TopologyGenerator\Environment;
use Hanaboso\PipesFramework\TopologyGenerator\HostMapper;

/**
 * Class GeneratorFactory
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose
 */
class GeneratorFactory
{

    /**
     * @var string
     */
    private $targetDir;
    /**
     * @var string
     */
    private $network;
    /**
     * @var VolumePathDefinitionFactory
     */
    private $volumePathDefinitionFactory;

    /**
     * GeneratorFactory constructor.
     *
     * @param string                      $targetDir
     * @param string                      $network
     * @param VolumePathDefinitionFactory $volumePathDefinitionFactory
     */
    public function __construct(
        string $targetDir,
        string $network,
        VolumePathDefinitionFactory $volumePathDefinitionFactory
    )
    {
        $this->targetDir                   = $targetDir;
        $this->network                     = $network;
        $this->volumePathDefinitionFactory = $volumePathDefinitionFactory;
    }

    /**
     * @return Generator
     */
    public function create(): Generator
    {
        return new Generator(new Environment(), new HostMapper(), $this->targetDir, $this->network, $this->volumePathDefinitionFactory);
    }

}