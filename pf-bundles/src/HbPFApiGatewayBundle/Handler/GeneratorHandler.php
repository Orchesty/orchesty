<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 21.9.17
 * Time: 8:41
 */

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\GeneratorFactory;
use InvalidArgumentException;

/**
 * Class GeneratorHandler
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler
 */
class GeneratorHandler
{

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var null|string
     */
    protected $rootDirectory;

    /**
     * @var string
     */
    protected $network;

    /**
     * @var StartingPointHandler
     */
    protected $startingPointHandler;
    /**
     * @var string
     */
    private $dstDirectory;

    /**
     * GeneratorHandler constructor.
     *
     * @param DocumentManager      $dm
     * @param StartingPointHandler $startingPointHandler
     * @param string|null          $rootDirectory
     * @param string               $dstDirectory
     * @param string               $network
     */
    public function __construct(
        DocumentManager $dm,
        StartingPointHandler $startingPointHandler,
        string $rootDirectory,
        string $dstDirectory = 'topology',
        string $network
    )
    {
        $this->dm                   = $dm;
        $this->startingPointHandler = $startingPointHandler;
        $this->rootDirectory        = rtrim($rootDirectory, '/');
        $this->dstDirectory         = ltrim($dstDirectory, '/');
        $this->network              = $network;
    }

    /**
     * @param string $topologyId
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function generateTopology(string $topologyId): bool
    {
        if ($this->network == NULL) {
            throw new InvalidArgumentException('Missing network definition');
        }

        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);
        $nodes    = $this->dm->getRepository(Node::class)->findBy([
            'topology' => $topologyId,
        ]);

        if (!$nodes) {
            return FALSE;
        }

        $dstDirectorPath      = sprintf('%s/%s', $this->rootDirectory, $this->dstDirectory);
        $dstTopologyDirectory = Generator::getTopologyDir($topology, $dstDirectorPath);

        if (!file_exists($dstTopologyDirectory)) {
            $generatorFactory = new GeneratorFactory($dstDirectorPath, $this->network);
            $generator        = $generatorFactory->create();
            $generator->generate($topology, $nodes);
        }

        $this->startingPointHandler->run($topology->getId(), $nodes[0]->getId());

        return TRUE;
    }

    /**
     * @param string $network
     */
    public function setNetwork(string $network): void
    {
        $this->network = $network;
    }

}
