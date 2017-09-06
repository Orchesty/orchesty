<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Node;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;

/**
 * Class NodeRepository
 *
 * @package Hanaboso\PipesFramework\Commons\Node
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

}