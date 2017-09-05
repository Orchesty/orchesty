<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;

/**
 * Class NodeManager
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Manager
 */
class NodeManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * TopologyManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     */
    function __construct(DatabaseManagerLocator $dml)
    {
        $this->dm = $dml->getDm();
    }

    /**
     * @param Node  $node
     * @param array $data
     *
     * @return Node
     */
    public function updateNode(Node $node, array $data): Node
    {
        $node->setEnabled($data['enabled']);
        $this->dm->flush();

        return $node;
    }

}