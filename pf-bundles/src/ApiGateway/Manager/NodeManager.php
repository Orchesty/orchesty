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
        if (isset($data['enabled'])) {
            if ($node->getHandler() != HandlerEnum::EVENT) {
                throw new NodeException(
                    sprintf('Trying to enable/disable a non event Node'),
                    NodeException::DISALLOWED_ACTION_ON_NON_EVENT_NODE
                );
            }

            $node->setEnabled($data['enabled']);
        } else {
            $node
                ->setName($data['name'])
                ->setType($data['type'])
                ->setHandler($data['handler']);
        }

        $this->dm->flush();

        return $node;
    }

}