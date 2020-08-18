<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesPhpSdk\Database\Document\Node;

/**
 * Class NodeManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class NodeManager
{

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * NodeManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     */
    function __construct(DatabaseManagerLocator $dml)
    {
        /** @var DocumentManager $dm */
        $dm       = $dml->getDm();
        $this->dm = $dm;
    }

    /**
     * @param Node    $node
     * @param mixed[] $data
     *
     * @return Node
     * @throws NodeException
     * @throws MongoDBException
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
                ->setTopology($data['topology'])
                ->setHandler($data['handler']);
        }

        $this->dm->flush();

        return $node;
    }

}
