<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 21.9.17
 * Time: 8:41
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Enum\DatabaseFilterEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\DestroyTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\GenerateTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\StartTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\StopTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\TopologyActionsFactory;
use Hanaboso\PipesFramework\TopologyGenerator\Exception\TopologyGeneratorException;

/**
 * Class GeneratorHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
class GeneratorHandler
{

    public const MODE_SWARM = 'swarm';

    public const MODE_COMPOSE = 'compose';

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
    protected $dstDirectory;

    /**
     * @var TopologyActionsFactory
     */
    protected $actionsFactory;

    /**
     * @var string
     */
    protected $deploymentPrefix;

    /**
     * @var string
     */
    protected $mode;

    /**
     * GeneratorHandler constructor.
     *
     * @param DocumentManager        $dm
     * @param string                 $dstDirectory
     * @param string                 $network
     * @param TopologyActionsFactory $actionsFactory
     * @param string                 $deploymentPrefix
     * @param string                 $mode
     */
    public function __construct(
        DocumentManager $dm,
        string $dstDirectory,
        string $network,
        TopologyActionsFactory $actionsFactory,
        string $deploymentPrefix,
        string $mode = self::MODE_SWARM
    )
    {
        $this->dm               = $dm;
        $this->dstDirectory     = $dstDirectory;
        $this->network          = $network;
        $this->actionsFactory   = $actionsFactory;
        $this->deploymentPrefix = $deploymentPrefix;
        $this->mode             = $mode;
    }

    /**
     * @param string $topologyId
     *
     * @return bool
     * @throws TopologyGeneratorException
     */
    public function generateTopology(string $topologyId): bool
    {

        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);
        $nodes    = $this->dm->getRepository(Node::class)->findBy([
            'topology' => $topologyId,
        ]);

        if ($topology && !empty($nodes)) {
            /** @var GenerateTopologyActions $actions */
            $actions = $this->actionsFactory->getTopologyAction(TopologyActionsFactory::GENERATE, $this->getMode());
            //TODO: add two generate choices
            $res = $actions->generateTopology(
                $topology,
                $nodes,
                $this->dstDirectory,
                $this->network,
                self::getStackName($this->deploymentPrefix, $topologyId)
            );

            return $res;
        }

        throw new TopologyGeneratorException(
            sprintf('Generate topology %s failed', $topologyId),
            TopologyGeneratorException::GENERATE_TOPOLOGY_FAILED
        );
    }

    /**
     * @param string $topologyId
     *
     * @return array|null
     * @throws TopologyGeneratorException
     */
    public function runTopology(string $topologyId): ?array
    {
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

        if ($topology) {
            /** @var StartTopologyActions $actions */
            $actions = $this->actionsFactory->getTopologyAction(TopologyActionsFactory::START, $this->getMode());
            $res     = $actions->runTopology(
                $topology,
                $this->dstDirectory,
                self::getStackName($this->deploymentPrefix, $topologyId)
            );

            if ($res) {
                $dockerInfo = $actions->getTopologyInfo($topology, $this->deploymentPrefix);

                return $dockerInfo;
            } else {

                return NULL;
            }
        }

        throw new TopologyGeneratorException(
            sprintf('Run topology %s failed', $topologyId),
            TopologyGeneratorException::RUN_TOPOLOGY_FAILED
        );
    }

    /**
     * @param string $topologyId
     *
     * @return array|null
     * @throws TopologyGeneratorException
     */
    public function stopTopology(string $topologyId): ?array
    {
        if ($this->dm->getFilterCollection()->isEnabled(DatabaseFilterEnum::DELETED)) {
            $this->dm->getFilterCollection()->disable(DatabaseFilterEnum::DELETED);
        }
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

        if (!$this->dm->getFilterCollection()->isEnabled(DatabaseFilterEnum::DELETED)) {
            $this->dm->getFilterCollection()->enable(DatabaseFilterEnum::DELETED);
        }

        if ($topology) {
            /** @var StopTopologyActions $actions */
            $actions = $this->actionsFactory->getTopologyAction(TopologyActionsFactory::STOP, $this->getMode());
            $res     = $actions->stopTopology(
                $topology,
                $this->dstDirectory,
                self::getStackName($this->deploymentPrefix, $topologyId)
            );

            if ($res) {
                $dockerInfo = $actions->getTopologyInfo($topology, $this->deploymentPrefix);

                return $dockerInfo;
            } else {
                return NULL;
            }
        }

        throw new TopologyGeneratorException(
            sprintf('Stop topology %s failed', $topologyId),
            TopologyGeneratorException::STOP_TOPOLOGY_FAILED
        );
    }

    /**
     * @param string $topologyId
     *
     * @return bool
     * @throws TopologyGeneratorException
     */
    public function destroyTopology(string $topologyId): bool
    {
        if ($this->dm->getFilterCollection()->isEnabled(DatabaseFilterEnum::DELETED)) {
            $this->dm->getFilterCollection()->disable(DatabaseFilterEnum::DELETED);
        }

        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);
        $nodes    = $this->dm->getRepository(Node::class)->findBy([
            'topology' => $topologyId,
        ]);

        if (!$this->dm->getFilterCollection()->isEnabled(DatabaseFilterEnum::DELETED)) {
            $this->dm->getFilterCollection()->enable(DatabaseFilterEnum::DELETED);
        }

        if ($topology && !empty($nodes)) {
            /** @var DestroyTopologyActions $actions */
            $actions = $this->actionsFactory->getTopologyAction(TopologyActionsFactory::DESTROY, $this->getMode());
            $res     = $actions->deleteTopologyDir($topology, $this->dstDirectory, $this->deploymentPrefix);
            $actions->deleteQueues($topology, $nodes);

            return $res;
        }

        throw new TopologyGeneratorException(
            sprintf('Destroy topology %s failed', $topologyId),
            TopologyGeneratorException::TOPOLOGY_NOT_FOUND
        );
    }

    /**
     * @param string $topologyId
     *
     * @return array
     * @throws TopologyGeneratorException
     */
    public function infoTopology(string $topologyId): array
    {
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

        if ($topology) {
            /** @var StopTopologyActions $actions */
            $actions    = $this->actionsFactory->getTopologyAction(TopologyActionsFactory::START, $this->getMode());
            $dockerInfo = $actions->getTopologyInfo($topology, $this->deploymentPrefix);

            return $dockerInfo;
        }

        throw new TopologyGeneratorException(
            sprintf('Topology %s not found', $topologyId),
            TopologyGeneratorException::TOPOLOGY_NOT_FOUND
        );
    }

    /**
     * @param string $network
     */
    public function setNetwork(string $network): void
    {
        $this->network = $network;
    }

    /**
     * @param string $prefix
     * @param string $topologyId
     *
     * @return string
     */
    public static function getStackName(string $prefix, string $topologyId): string
    {
        return sprintf('%s_%s', $prefix, substr($topologyId, 8));
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

}
