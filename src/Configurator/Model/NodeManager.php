<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\NodeRepository;

/**
 * Class NodeManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class NodeManager
{

    private const PREFETCH_MIN = 1;
    private const PREFETCH_MAX = 20;

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var NodeRepository
     */
    private NodeRepository $nodeRepository;

    /**
     * NodeManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param CronManager            $cronManager
     */
    function __construct(DatabaseManagerLocator $dml, private readonly CronManager $cronManager)
    {
        /** @var DocumentManager $dm */
        $dm       = $dml->getDm();
        $this->dm = $dm;

        $nodeRepo             = $dm->getRepository(Node::class);
        $this->nodeRepository = $nodeRepo;
    }

    /**
     * @param Node    $node
     * @param mixed[] $data
     *
     * @return Node
     * @throws MongoDBException
     * @throws NodeException
     */
    public function updateNode(Node $node, array $data): Node
    {
        if (isset($data['enabled'])) {
            if ($node->getHandler() != HandlerEnum::EVENT->value) {
                throw new NodeException(
                    'Trying to enable/disable a non event Node',
                    NodeException::DISALLOWED_ACTION_ON_NON_EVENT_NODE,
                );
            }

            $node->setEnabled($data['enabled']);
        } else if (isset($data['cron'])) {
            if ($node->getType() != TypeEnum::CRON->value) {
                throw new NodeException(
                    'Trying to set cron parameters on non cron Node',
                    NodeException::DISALLOWED_ACTION_ON_NON_EVENT_NODE,
                );
            }

            $node
                ->setCronParams($data['cron']['params'] ?? '')
                ->setCron($data['cron']['time'] ?? '');

            $this->cronManager->upsert($node);
        } else if (array_key_exists('prefetch', $data)) {
            $this->applyPrefetch($node, (int) $data['prefetch']);
        } else {
            $node
                ->setName($data['name'])
                ->setType($data['type'])
                ->setTopology($data['topology'])
                ->setHandler($data['handler']);
        }

        $this->dm->flush();

        return $node;
    }

    /**
     * Persists a new RabbitMQ consumer prefetch on the given node and marks
     * the parent topology as out-of-sync so the UI can prompt for republish.
     * Prefetch is stored inside the Node.systemConfigs JSON blob, which is
     * then read back by `TopologyConfigFactory` and pushed to the bridge via
     * `topology-generator`. The bridge only re-reads the config on republish,
     * which is why we flag the topology here.
     *
     * @throws NodeException
     */
    private function applyPrefetch(Node $node, int $prefetch): void
    {
        if ($prefetch < self::PREFETCH_MIN || $prefetch > self::PREFETCH_MAX) {
            throw new NodeException(
                sprintf(
                    'Prefetch must be an integer between %d and %d, got %d',
                    self::PREFETCH_MIN,
                    self::PREFETCH_MAX,
                    $prefetch,
                ),
                NodeException::DISALLOWED_ACTION_ON_NON_EVENT_NODE,
            );
        }

        $allowedTypes = [
            TypeEnum::CONNECTOR->value,
            TypeEnum::BATCH->value,
            TypeEnum::BATCH_CONNECTOR->value,
            TypeEnum::CUSTOM->value,
        ];

        if (!in_array($node->getType(), $allowedTypes, TRUE)) {
            throw new NodeException(
                sprintf('Prefetch is not configurable for node type "%s"', $node->getType()),
                NodeException::DISALLOWED_ACTION_ON_NON_EVENT_NODE,
            );
        }

        $configs = $node->getSystemConfigs() ?? new SystemConfigDto();
        $configs->setPrefetch($prefetch);
        $node->setSystemConfigs($configs);

        $topologyId = $node->getTopology();
        if ($topologyId !== '') {
            /** @var Topology|null $topology */
            $topology = $this->dm->getRepository(Topology::class)->find($topologyId);
            if ($topology) {
                $topology->setBridgeOutOfSync(TRUE);
            }
        }
    }

    /**
     * @param string $topologyId
     *
     * @return mixed[]
     */
    public function getNodes(string $topologyId): array
    {
        $nodes = $this->nodeRepository->getEventNodesByTopology($topologyId);

        $items = [];
        foreach ($nodes as $node) {
            $items[] = $node->toArray();
        }

        return ['items' => $items];
    }

    /**
     * @param string $topologyId
     * @param string $nodeName
     *
     * @return mixed[]
     */
    public function getTopologyNodesByName(string $topologyId, string $nodeName): array
    {
        return array_filter(
            $this->getNodes($topologyId)['items'],
            static fn($value) => $value['name'] === $nodeName,
        );
    }

    /**
     * @param string $id
     *
     * @return Node
     * @throws LockException
     * @throws MappingException
     * @throws NodeException
     */
    public function getNodeById(string $id): Node
    {
        /** @var Node|null $res */
        $res = $this->nodeRepository->find($id);

        if (!$res) {
            throw new NodeException(
                sprintf('Node with [%s] id was not found.', $id),
                NodeException::NODE_NOT_FOUND,
            );
        }

        return $res;
    }

}
