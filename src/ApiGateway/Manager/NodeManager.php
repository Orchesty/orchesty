<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Exception\NodeException;
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
     * @throws NodeException
     */
    public function updateNode(Node $node, array $data): Node
    {
        if ($node->getHandler() == HandlerEnum::EVENT) {
            $node->setEnabled($data['enabled']);
            $this->dm->flush();

            return $node;
        }

        throw new NodeException(
            sprintf('Trying to update a non event Node'),
            NodeException::TRYING_TO_UPDATE_NON_EVENT_NODE
        );
    }

}