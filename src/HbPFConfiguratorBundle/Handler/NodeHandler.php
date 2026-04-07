<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;

/**
 * Class NodeHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class NodeHandler
{

    /**
     * NodeHandler constructor.
     *
     * @param NodeManager     $nodeManager
     * @param DocumentManager $dm
     * @param ServiceLocator  $serviceLocator
     */
    public function __construct(
        private NodeManager $nodeManager,
        private DocumentManager $dm,
        private ServiceLocator $serviceLocator,
    )
    {
    }

    /**
     * @param bool $all
     *
     * @return mixed[]
     */
    public function getTopologiesWithNodes(bool $all = FALSE): array
    {
        /** @var Topology[] $topologies */
        $topologies = $this
            ->dm
            ->getRepository(Topology::class)
            ->findBy($all ? [] : ['deleted' => FALSE]);

        $nodesQueryBuilder = $this
            ->dm
            ->getRepository(Node::class)
            ->createQueryBuilder();

        if (!$all) {
            $nodesQueryBuilder
                ->field('deleted')
                ->equals(FALSE)
                ->field('type')
                ->notIn([
                    TypeEnum::CRON->value,
                    TypeEnum::START->value,
                    TypeEnum::USER->value,
                    TypeEnum::WEBHOOK->value,
                ]);
        }

        /** @var Node[] $nodes */
        $nodes = $nodesQueryBuilder
            ->getQuery()
            ->toArray();

        $applications = $this->serviceLocator->getApplications('orchesty');

        $applicationsData     = [];
        $topologiesData       = [];
        $topologyVersionsData = [];
        $nodesData            = [];
        $topologyTree         = [];
        $applicationTree      = [];

        foreach ($applications as $sdk) {
            $sdkName = $sdk['name'] ?? '';

            foreach ($sdk['applications'] ?? [] as $application) {
                if (isset($application['key'], $application['name'])) {
                    $compositeKey                    = sprintf('%s:%s', $sdkName, $application['key']);
                    $applicationsData[$compositeKey] = $application['name'];
                    $applicationTree[$compositeKey]  = [];
                }
            }
        }

        foreach ($topologies as $topology) {
            $topologyId                        = $topology->getId();
            $topologiesData[$topologyId]       = $this->formatName($topology->getName());
            $topologyVersionsData[$topologyId] = $topology->getVersion();
            $topologyTree[$topologyId]         = [];
        }

        foreach ($nodes as $node) {
            $nodeId             = $node->getId();
            $topologyId         = $node->getTopology();
            $applicationId      = $node->getApplication();
            $nodesData[$nodeId] = $this->formatName($node->getName());

            if (isset($topologyTree[$topologyId])) {
                $topologyTree[$topologyId][] = $nodeId;
            }

            if ($applicationId) {
                $sdkName      = $node->getSdk();
                $compositeKey = $sdkName ? sprintf('%s:%s', $sdkName, $applicationId) : $applicationId;

                if (isset($applicationTree[$compositeKey])) {
                    $applicationTree[$compositeKey][] = $nodeId;
                }
            }
        }

        asort($applicationsData);
        asort($topologiesData);
        asort($nodesData);

        return [
            'applications'     => $applicationsData,
            'applicationTree'  => $applicationTree,
            'nodes'            => $nodesData,
            'topologies'       => $topologiesData,
            'topologyTree'     => $topologyTree,
            'topologyVersions' => $topologyVersionsData,
        ];
    }

    /**
     * @return mixed[]
     */
    public function getConnectorNodes(): array
    {
        /** @var \Hanaboso\PipesFramework\Database\Repository\NodeRepository $repo */
        $repo  = $this->dm->getRepository(Node::class);
        $nodes = $repo->getConnectorNodes();

        $items = [];
        foreach ($nodes as $node) {
            $items[] = [
                '_id'         => $node->getId(),
                'name'        => $node->getName(),
                'topology_id' => $node->getTopology(),
                'application' => $node->getApplication(),
                'type'        => $node->getType(),
            ];
        }

        return ['items' => $items];
    }

    /**
     * @param string $topologyId
     *
     * @return mixed[]
     */
    public function getNodes(string $topologyId): array
    {
        return $this->nodeManager->getNodes($topologyId);
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws LockException
     * @throws MappingException
     * @throws NodeException
     */
    public function getNode(string $id): array
    {
        return $this->nodeManager->getNodeById($id)->toArray();
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws NodeException
     */
    public function updateNode(string $id, array $data): array
    {
        $node = $this->nodeManager->updateNode($this->nodeManager->getNodeById($id), $data);

        return $node->toArray();
    }

    /**
     * @param string $name
     * @return string
     */
    private function formatName(string $name): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $name));
    }

}
