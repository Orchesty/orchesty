<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\RequestHandler;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler as HbPFTopologyHandler;

/**
 * Class TopologyHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
class TopologyHandler extends HbPFTopologyHandler
{

    /**
     * @var SystemManager
     */
    private $sysManager;

    /**
     * TopologyHandler constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param TopologyManager        $manager
     * @param SystemManager          $sysManager
     * @param RequestHandler         $requestHandler
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        TopologyManager $manager,
        SystemManager $sysManager,
        RequestHandler $requestHandler
    )
    {
        parent::__construct($dml, $manager, $requestHandler);
        $this->sysManager = $sysManager;
    }

    /**
     * @param string $id
     *
     * @return bool
     * @throws CleverConnectorsException
     * @throws SystemException
     * @throws MongoDBException
     * @throws CurlException
     */
    public function deleteTopologyById(string $id): bool
    {
        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $id]);
        if (!$topology) {
            throw new CleverConnectorsException(
                'Topology with given name not found.',
                CleverConnectorsException::TOPOLOGY_NOT_FOUND
            );
        }

        $webhooks = [];
        $nodes    = [];
        $syncs    = [];
        /** @var TopologyRepository $repo */
        $repo       = $this->dm->getRepository(Topology::class);
        $topologies = $repo->getTopologiesCountByName($topology->getName());

        if ($topologies === 1) {
            $nodes    = $this->dm->getRepository(Node::class)->findBy(['topology' => $id]);
            $syncs    = $this->dm->getRepository(LastSync::class)->findBy(['topologyName' => $topology->getName()]);
            $webhooks = $this->getWebhooks($topology->getName());
        }

        $this->sysManager->deleteTopology($topology, $webhooks, $nodes, $syncs);

        return TRUE;
    }

    /**
     * @param string $topologyName
     *
     * @return bool
     * @throws SystemException
     * @throws MongoDBException
     * @throws CurlException
     */
    public function deleteWebhooksByTopologyName(string $topologyName): bool
    {
        $users = $this->getWebhooks($topologyName);
        $this->sysManager->deleteTopology(NULL, $users, [], []);

        return TRUE;
    }

    /**
     * -------------------------------------- HELPERS --------------------------------------
     */

    /**
     * @param string $topologyName
     *
     * @return array
     * @throws MongoDBException
     */
    private function getWebhooks(string $topologyName): array
    {
        /** @var WebhookRepository $repo */
        $repo = $this->dm->getRepository(Webhook::class);

        return $repo->getWebhooks($topologyName);
    }

}