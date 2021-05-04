<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;

/**
 * Class NodeHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class NodeHandler
{

    /**
     * @var ObjectRepository<Node>&NodeRepository
     */
    private NodeRepository $nodeRepository;

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * NodeHandler constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param NodeManager            $manager
     */
    public function __construct(DatabaseManagerLocator $dml, private NodeManager $manager)
    {
        /** @var DocumentManager $dm */
        $dm                   = $dml->getDm();
        $this->dm             = $dm;
        $this->nodeRepository = $this->dm->getRepository(Node::class);
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
            $items[] = $this->getNodeData($node);
        }

        return ['items' => $items];
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
        return $this->getNodeData($this->getNodeById($id));
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws LockException
     * @throws MappingException
     * @throws NodeException
     * @throws MongoDBException
     */
    public function updateNode(string $id, array $data): array
    {
        $node = $this->manager->updateNode($this->getNodeById($id), $data);

        return $this->getNodeData($node);
    }

    /**
     * @param Node $node
     *
     * @return mixed[]
     */
    private function getNodeData(Node $node): array
    {
        return [
            '_id'         => $node->getId(),
            'name'        => $node->getName(),
            'topology_id' => $node->getTopology(),
            'next'        => $node->getNext(),
            'type'        => $node->getType(),
            'handler'     => $node->getHandler(),
            'enabled'     => $node->isEnabled(),
            'schema_id'   => $node->getSchemaId(),
        ];
    }

    /**
     * @param string $id
     *
     * @return Node
     * @throws NodeException
     * @throws LockException
     * @throws MappingException
     */
    private function getNodeById(string $id): Node
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
