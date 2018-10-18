<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Loader;

use Hanaboso\PipesFramework\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LongRunningNodeLoader
 *
 * @package Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Loader
 */
final class LongRunningNodeLoader
{

    private const PREFIX = 'hbpf.long_running';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * LongRunningNodeLoader constructor.
     *
     * @param ContainerInterface $container
     */
    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $id
     *
     * @return LongRunningNodeInterface
     * @throws LongRunningNodeException
     */
    public function getLongRunningNode(string $id): LongRunningNodeInterface
    {
        $name = sprintf('%s.%s', self::PREFIX, $id);
        if ($this->container->has($name)) {
            /** @var LongRunningNodeInterface $node */
            $node = $this->container->get($name);

            return $node;
        }

        throw new LongRunningNodeException(
            sprintf('Service for [%s] long running node was not found', $id),
            LongRunningNodeException::LONG_RUNNING_SERVICE_NOT_FOUND
        );
    }

}