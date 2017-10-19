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
     * ActionsAbstract constructor.
     *
     * @param DockerHandler $dockerHandler
     */
    public function __construct(DockerHandler $dockerHandler)
    {
        $this->dockerHandler = $dockerHandler;
    }

    /**
     * @param Topology $topology
     *
     * @return array
     */
    public function getTopologyInfo(Topology $topology): array
    {
        $dockerInfo = $this->dockerHandler->getTopologyInfo(
            GeneratorUtils::dokerizeName($topology->getId(), $topology->getName())
        );

        return $dockerInfo;
    }

    /**
     * @param string $dstTopologyDirectory
     *
     * @return DockerComposeCli
     */
    protected function getDockerComposeCli(string $dstTopologyDirectory): DockerComposeCli
    {
        return new DockerComposeCli($dstTopologyDirectory);
    }

}
