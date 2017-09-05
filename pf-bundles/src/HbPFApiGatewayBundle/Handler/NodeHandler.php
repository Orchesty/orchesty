<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\ApiGateway\Manager\NodeManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Exception\NodeException;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Node\NodeRepository;
use Hanaboso\PipesFramework\Commons\Utils\UriParams;

/**
 * Class NodeHandler
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler
 */
class NodeHandler
{

    /**
     * @var DocumentRepository|NodeRepository
     */
    private $nodeRepository;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var NodeManager
     */
    private $manager;

    /**
     * NodeHandler constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param NodeManager            $manager
     */
    public function __construct(DatabaseManagerLocator $dml, NodeManager $manager)
    {
        $this->dm             = $dml->getDm();
        $this->nodeRepository = $this->dm->getRepository(Node::class);
        $this->manager        = $manager;
    }

    /**
     * @param string $topologyId
     * @param null   $limit
     * @param null   $offset
     * @param null   $orderBy
     *
     * @return array
     */
    public function getNodes(string $topologyId, $limit = NULL, $offset = NULL, $orderBy = NULL): array
    {
        $sort  = UriParams::parseOrderBy($orderBy);
        $nodes = $this->nodeRepository->findBy(['topology' => $topologyId], $sort, $limit, $offset);

        $data = [];
        foreach ($nodes as $node) {
            $data['items'][] = $this->getNodeData($node);
        }

        $data['total']  = $this->nodeRepository->getTotalCount();
        $data['limit']  = $limit;
        $data['count']  = count($data['items']);
        $data['offset'] = $offset;

        return $data;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getNode(string $id): array
    {
        return $this->getNodeData($this->getNodeById($id));
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     */
    public function updateNode(string $id, array $data): array
    {
        $node = $this->manager->updateNode($this->getNodeById($id), $data);

        return $this->getNodeData($node);
    }

    /**
     * @param Node $node
     *
     * @return array
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
        ];
    }

    /**
     * @param string $id
     *
     * @return Node
     * @throws NodeException
     */
    private function getNodeById(string $id): Node
    {
        $res = $this->nodeRepository->find($id);

        if (!$res) {
            throw new NodeException(
                sprintf('Node with [%s] id was not found.', $id),
                NodeException::NODE_NOT_FOUND
            );
        }

        return $res;
    }

}