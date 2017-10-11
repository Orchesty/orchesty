<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
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
     * @param GeneratorHandler       $generatorHandler
     * @param CurlManagerInterface   $curlManager
     * @param SystemManager          $sysManager
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        TopologyManager $manager,
        GeneratorHandler $generatorHandler,
        CurlManagerInterface $curlManager,
        SystemManager $sysManager
    )
    {
        parent::__construct($dml, $manager, $generatorHandler, $curlManager);
        $this->sysManager = $sysManager;
    }

    /**
     * @param string $id
     *
     * @return bool
     * @throws CleverConnectorsException
     */
    public function deleteTopologyById(string $id): bool
    {
        /** @var Topology $topology */
        $topology = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $id]);
        if (!$topology) {
            throw new CleverConnectorsException(
                'Topology with given name not found.',
                CleverConnectorsException::TOPOLOGY_NOT_FOUND
            );
        }

        $users      = [];
        $nodes      = [];
        $syncs      = [];
        $topologies = $this->dm->getRepository(Topology::class)->findBy(['name' => $topology->getName()]);

        if (count($topologies) === 1) {
            $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $id]);
            $syncs = $this->dm->getRepository(LastSync::class)->findBy(['topologyName' => $topology->getName()]);
            $users = $this->getUsers($topology->getName());
        }

        $this->sysManager->deleteTopology($topology, $users, $nodes, $syncs);

        return TRUE;
    }

    /**
     * @param string $topologyName
     *
     * @return bool
     */
    public function deleteWebhooksByTopologyName(string $topologyName): bool
    {
        $users = $this->getUsers($topologyName);
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
     */
    private function getUsers(string $topologyName): array
    {
        /** @var WebhookRepository $repo */
        $repo  = $this->dm->getRepository(Webhook::class);
        return $repo->getWebhooksForTopology($topologyName);
    }

}