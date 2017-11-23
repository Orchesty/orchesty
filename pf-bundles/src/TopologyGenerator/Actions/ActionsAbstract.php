<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 17:30
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Commons\Docker\Handler\DockerHandler;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\DockerComposeCli;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;

/**
 * Class ActionsAbstract
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\Actions
 */
abstract class ActionsAbstract
{

    /**
     * @var DockerHandler
     */
    protected $dockerHandler;

    /**
     * @var string
     */
    protected $mode;

    /**
     * ActionsAbstract constructor.
     *
     * @param DockerHandler $dockerHandler
     * @param string        $mode
     */
    public function __construct(DockerHandler $dockerHandler, string $mode)
    {
        $this->dockerHandler = $dockerHandler;
        $this->mode          = $mode;
    }

    /**
     * @param Topology $topology
     * @param string   $deploymentPrefix
     *
     * @return array
     */
    public function getTopologyInfo(Topology $topology, string $deploymentPrefix): array
    {
        switch ($this->mode) {
            case GeneratorHandler::MODE_SWARM:
                $dockerInfo = $this->dockerHandler->getTopologyStackInfo(
                    GeneratorHandler::getStackName($deploymentPrefix, $topology->getId())
                );
                break;
            case GeneratorHandler::MODE_COMPOSE:
                $dockerInfo = $this->dockerHandler->getTopologyInfo(
                    GeneratorUtils::dokerizeName($topology->getId(), $topology->getName())
                );
                break;
            default:
                return [];
        }

        return $dockerInfo;
    }

    /**
     * @param string $dstTopologyDirectory
     * @param string $topologyPrefix
     *
     * @return DockerComposeCli
     */
    protected function getDockerComposeCli(string $dstTopologyDirectory, string $topologyPrefix): DockerComposeCli
    {
        return new DockerComposeCli($dstTopologyDirectory, $topologyPrefix);
    }

}
