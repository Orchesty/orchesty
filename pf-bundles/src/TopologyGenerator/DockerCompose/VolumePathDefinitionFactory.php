<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 8.10.17
 * Time: 22:10
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\Configurator\Document\Topology;

/**
 * Class VolumePathDefinitionFactory
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose
 */
class VolumePathDefinitionFactory
{

    /**
     * @var string|null
     */
    protected $projectSourcePath;

    /**
     * VolumePathDefinitionFactory constructor.
     *
     * @param null|string $projectSourcePath
     */
    public function __construct(?string $projectSourcePath = NULL)
    {
        $this->projectSourcePath = $projectSourcePath;
    }

    /**
     * @param Topology $topology
     *
     * @return VolumePathDefinition
     */
    public function create(Topology $topology): VolumePathDefinition
    {
        return new VolumePathDefinition($topology, $this->projectSourcePath);
    }

}
