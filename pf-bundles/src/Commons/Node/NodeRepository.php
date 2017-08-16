<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/10/17
 * Time: 10:43 AM
 */

namespace Hanaboso\PipesFramework\Commons\Node;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodeRepository
 *
 * @package Hanaboso\PipesFramework\Commons\Node
 */
class NodeRepository
{

    private const NODE_SERVICE_PREFIX = 'hbpf.nodes.';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * NodeRepository constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $node
     *
     * @return NodeInterface
     */
    public function get(string $node): NodeInterface
    {
        /** @var NodeInterface $node */
        $node = $this->container->get(self::NODE_SERVICE_PREFIX . $node);

        return $node;
    }

}
