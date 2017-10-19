<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 19:57
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Commons\Docker\Handler\DockerHandler;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\GeneratorFactory;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinitionFactory;

/**
 * Class GenerateTopologyActions
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\Actions
 */
class GenerateTopologyActions extends ActionsAbstract
{

    /**
     * @var VolumePathDefinitionFactory
     */
    protected $volumePathDefinition;

    /**
     * GenerateTopologyActions constructor.
     *
     * @param DockerHandler               $dockerHandler
     * @param VolumePathDefinitionFactory $volumePathDefinition
     */
    public function __construct(DockerHandler $dockerHandler, VolumePathDefinitionFactory $volumePathDefinition)
    {
        parent::__construct($dockerHandler);
        $this->volumePathDefinition = $volumePathDefinition;
    }

    /**
     * @param Topology $topology
     * @param array    $nodes
     * @param string   $dstDirectory
     * @param string   $network
     *
     * @return bool
     */
    public function generateTopology(Topology $topology, array $nodes, string $dstDirectory, string $network): bool
    {
        $generator = $this->getGenerator(
            $dstDirectory,
            $network,
            $this->volumePathDefinition
        );

        $generator->generate($topology, $nodes);

        return TRUE;
    }

    /**
     * @param string                      $dstDirectory
     * @param string                      $network
     * @param VolumePathDefinitionFactory $volumePathDefinition
     *
     * @return Generator
     */
    protected function getGenerator(
        string $dstDirectory,
        string $network,
        VolumePathDefinitionFactory $volumePathDefinition
    ): Generator
    {
        $generator = new GeneratorFactory(
            $dstDirectory,
            $network,
            $volumePathDefinition
        );

        return $generator->create();
    }

}
