<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 21.8.17
 * Time: 14:43
 */

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Handler;

use Hanaboso\PipesFramework\Commons\Message\MessageInterface;
use Hanaboso\PipesFramework\Commons\Node\NodeInterface;
use Hanaboso\PipesFramework\Commons\Node\NodeRepository;

/**
 * Class NodeHandler
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Handler
 */
class NodeHandler
{

    /**
     * @var NodeRepository
     */
    private $nodeRepository;

    /**
     * NodeHandler constructor.
     *
     * @param NodeRepository $nodeRepository
     */
    public function __construct(NodeRepository $nodeRepository)
    {
        $this->nodeRepository = $nodeRepository;
    }

    /**
     * @param string           $id
     * @param MessageInterface $message
     *
     * @return mixed
     */
    public function processData(string $id, MessageInterface $message)
    {
        /** @var NodeInterface $node */
        $node = $this->nodeRepository->get($id);

        return $node->processData($id, $message);
    }

}
