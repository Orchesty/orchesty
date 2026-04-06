<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Configurator\Handler;

use DateTime;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Model\TopologyTester;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyProgressRepository;
use Hanaboso\PipesFramework\Database\Document\Topology as BaseTopology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler as BaseTopologyHandler;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler\UserTaskHandler;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\PipesFrameworkEnterprise\Database\Document\Topology;

/**
 * Class TopologyHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Configurator\Handler
 */
final class TopologyHandler extends BaseTopologyHandler
{

    /**
     * @var TopologyProgressRepository
     */
    private TopologyProgressRepository $topologyProgressRepository;

    /**
     * TopologyHandler constructor.
     *
     * @param DatabaseManagerLocator  $dml
     * @param TopologyManager         $topologyManager
     * @param NodeManager             $nodeManager
     * @param TopologyGeneratorBridge $generatorBridge
     * @param UserTaskHandler         $userTaskHandler
     * @param TopologyTester          $topologyTester
     * @param class-string<BaseTopology> $topologyClass
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        TopologyManager $topologyManager,
        NodeManager $nodeManager,
        TopologyGeneratorBridge $generatorBridge,
        UserTaskHandler $userTaskHandler,
        TopologyTester $topologyTester,
        string $topologyClass = Topology::class,
    )
    {
        parent::__construct($dml, $topologyManager, $nodeManager, $generatorBridge, $userTaskHandler, $topologyTester, $topologyClass);

        /** @var TopologyProgressRepository $repo */
        $repo                              = $this->dm->getRepository(\Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::class);
        $this->topologyProgressRepository  = $repo;
    }

    /**
     * @return mixed[]
     */
    public function getRunningBridges(): array
    {
        /** @var BaseTopology[] $topologies */
        $topologies = $this->topologyRepository->findBy([
            'deleted'    => FALSE,
            'visibility' => TopologyStatusEnum::PUBLIC->value,
        ]);

        $items = [];
        $grouped = [];

        foreach ($topologies as $topology) {
            $topologyId = $topology->getId();

            /** @var int $runningProcesses */
            $runningProcesses = $this->topologyProgressRepository->createQueryBuilder()
                ->field('topologyId')->equals($topologyId)
                ->field('finished')->equals(NULL)
                ->count()
                ->getQuery()
                ->execute();

            /** @var int $trashCount */
            $trashCount = $this->dm->createQueryBuilder(\Hanaboso\PipesFramework\UserTask\Document\UserTask::class)
                ->field('topologyId')->equals($topologyId)
                ->field('type')->equals(UserTaskEnum::TRASH->value)
                ->count()
                ->getQuery()
                ->execute();

            $items[] = [
                '_id'              => $topologyId,
                'name'             => $topology->getName(),
                'version'          => $topology->getVersion(),
                'enabled'          => $topology->isEnabled(),
                'runningProcesses' => $runningProcesses,
                'trashCount'       => $trashCount,
            ];

            $grouped[$topology->getName()][] = $topology;
        }

        $reducible = 0;
        foreach ($grouped as $versions) {
            if (count($versions) > 1) {
                $reducible += count($versions) - 1;
            }
        }

        return [
            'items'   => $items,
            'summary' => [
                'total'     => count($topologies),
                'reducible' => $reducible,
            ],
        ];
    }

    /**
     * @param string $topologyId
     * @param bool   $forceCleanup
     *
     * @return void
     * @throws TopologyException
     * @throws MongoDBException
     */
    public function decommissionBridge(string $topologyId, bool $forceCleanup = FALSE): void
    {
        /** @var BaseTopology|null $topology */
        $topology = $this->topologyRepository->findOneBy(['id' => $topologyId]);

        if (!$topology) {
            throw new TopologyException(
                sprintf('Topology with [%s] id was not found.', $topologyId),
                TopologyException::TOPOLOGY_NOT_FOUND,
            );
        }

        if ($topology->getVisibility() !== TopologyStatusEnum::PUBLIC->value) {
            throw new TopologyException(
                sprintf('Topology [%s] is not published.', $topologyId),
            );
        }

        try {
            $this->generatorBridge->stopTopology($topologyId, TRUE);
        } catch (\Throwable) {
        }

        try {
            $this->generatorBridge->deleteTopology($topologyId);
        } catch (\Throwable) {
        }

        if ($forceCleanup) {
            $this->userTaskHandler->removeAllUserTasks($topologyId);

            $headers = $this->topologyManager->getHeadersForTopologyRunRequest();
            try {
                $this->generatorBridge->removeAllLimiterAndRepeaterMessages($topologyId, $headers);
            } catch (\Throwable) {
            }

            $this->dm->createQueryBuilder(\Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::class)
                ->updateMany()
                ->field('topologyId')->equals($topologyId)
                ->field('finished')->equals(NULL)
                ->field('finished')->set(new DateTime())
                ->field('nok')->set(1)
                ->getQuery()
                ->execute();
        }

        $this->topologyManager->unPublishTopology($topology);
    }

    /**
     * @param string $topologyId
     *
     * @return mixed[]
     * @throws TopologyException
     */
    public function restartBridge(string $topologyId): array
    {
        /** @var BaseTopology|null $topology */
        $topology = $this->topologyRepository->findOneBy(['id' => $topologyId]);

        if (!$topology) {
            throw new TopologyException(
                sprintf('Topology with [%s] id was not found.', $topologyId),
                TopologyException::TOPOLOGY_NOT_FOUND,
            );
        }

        if ($topology->getVisibility() !== TopologyStatusEnum::PUBLIC->value) {
            throw new TopologyException(
                sprintf('Topology [%s] is not published.', $topologyId),
            );
        }

        $result = $this->generatorBridge->restartTopology($topologyId);

        return [
            'success'    => $result->getStatusCode() === 200,
            'statusCode' => $result->getStatusCode(),
        ];
    }

    /**
     * @param BaseTopology $topology
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    protected function getTopologyData(BaseTopology $topology): array
    {
        $data = parent::getTopologyData($topology);

        if ($topology instanceof Topology) {
            $data['mcp_description'] = $topology->getMcpDescription();
        }

        return $data;
    }

}
