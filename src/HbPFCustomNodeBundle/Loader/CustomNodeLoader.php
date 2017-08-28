<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:36 PM
 */

namespace Hanaboso\PipesFramework\HbPFCustomNodeBundle\Loader;

use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomNodeLoader
 *
 * @package Hanaboso\PipesFramework\HbPFCustomNodeBundle\Loader
 */
final class CustomNodeLoader
{

    public const PREFIX = 'hbpf.custom_node';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * CustomNodeLoader constructor.
     *
     * @param ContainerInterface $container
     */
    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $node
     *
     * @return CustomNodeInterface
     * @throws CustomNodeException
     */
    public function get(string $node): CustomNodeInterface
    {
        $name = sprintf('%s.%s', self::PREFIX, $node);
        if ($this->container->has($name)) {
            /** @var CustomNodeInterface $node */
            $node = $this->container->get($name);

            return $node;
        }

        throw new CustomNodeException(
            sprintf('Node [%s] not found.', $node),
            CustomNodeException::CUSTOM_NODE_SERVICE_NOT_FOUND
        );
    }

}