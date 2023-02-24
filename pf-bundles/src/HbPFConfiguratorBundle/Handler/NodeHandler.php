<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;

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
     * @param NodeManager $nodeManager
     */
    public function __construct(private NodeManager $nodeManager)
    {
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
     * @throws NodeException
     * @throws MongoDBException
     */
    public function updateNode(string $id, array $data): array
    {
        $node = $this->nodeManager->updateNode($this->nodeManager->getNodeById($id), $data);

        return $node->toArray();
    }

}
