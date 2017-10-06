<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 21.9.17
 * Time: 8:41
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\DockerComposeCli;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\GeneratorFactory;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinitionFactory;
use InvalidArgumentException;

/**
 * Class GeneratorHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
class GeneratorHandler
{

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var string
     */
    protected $network;

    /**
     * @var string
     */
    private $dstDirectory;

    /**
     * @var VolumePathDefinitionFactory
     */
    private $volumePathDefinition;

    /**
     * GeneratorHandler constructor.
     *
     * @param DocumentManager             $dm
     * @param string                      $dstDirectory
     * @param string                      $network
     * @param VolumePathDefinitionFactory $volumePathDefinition
     */
    public function __construct(
        DocumentManager $dm,
        string $dstDirectory,
        string $network,
        VolumePathDefinitionFactory $volumePathDefinition
    )
    {
        $this->dm                   = $dm;
        $this->dstDirectory         = $dstDirectory;
        $this->network              = $network;
        $this->volumePathDefinition = $volumePathDefinition;
    }

    /**
     * @param string $topologyId
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function generateTopology(string $topologyId): bool
    {
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);
        $nodes    = $this->dm->getRepository(Node::class)->findBy([
            'topology' => $topologyId,
        ]);

        if (!is_array($nodes) || empty($nodes)) {
            return FALSE;
        } else {
            $this->generate($topology, $nodes);
        }

        return TRUE;
    }

    public function runTopology(string $topologyId): bool
    {
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

        if ($topology) {
            $dstTopologyDirectory = Generator::getTopologyDir($topology, $this->dstDirectory);
            $cli                  = new DockerComposeCli($dstTopologyDirectory);
            $result               = $cli->up();

            return (bool) $result;
        }

        return FALSE;
    }

    /**
     * @param Topology $topology
     * @param Node[]   $nodes
     */
    protected function generate(Topology $topology, array $nodes): void
    {
        $dstTopologyDirectory = Generator::getTopologyDir($topology, $this->dstDirectory);

        if (!file_exists($dstTopologyDirectory)) {
            $generatorFactory = new GeneratorFactory($this->dstDirectory, $this->network, $this->volumePathDefinition);
            $generator        = $generatorFactory->create();
            $generator->generate($topology, $nodes);
        }
    }

    /**
     * @param string $network
     */
    public function setNetwork(string $network): void
    {
        $this->network = $network;
    }

}
