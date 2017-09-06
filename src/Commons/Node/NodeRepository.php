<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Node;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;

/**
 * Class NodeRepository
 *
 * @MongoDB\Document(repositoryClass="Hanaboso\PipesFramework\Commons\Node\NodeRepository")
 *
 * @package Hanaboso\PipesFramework\Commons\Node\Document
 */
class NodeRepository extends DocumentRepository
{

    /**
     * @param string $topologyId
     *
     * @return array
     */
    public function getEventNodesByTopology(string $topologyId): array
    {
        $criteria = [
            'topology' => $topologyId,
            'handler'  => HandlerEnum::EVENT,
        ];

        return $this->findBy($criteria);
    }

    /**
     * @param string $topologyId
     * @param string $nodeId
     *
     * @return Node|null|object
     */
    public function getNodeByTopology(string $topologyId, string $nodeId): ?Node
    {
        $criteria = [
            'id'       => $nodeId,
            'topology' => $topologyId,
        ];

        return $this->findOneBy($criteria);
    }

}