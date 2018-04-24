<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;

/**
 * Class NodeManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
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