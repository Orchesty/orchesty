<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader;

use Hanaboso\CommonsBundle\Utils\NodeServiceLoader;
use Hanaboso\PipesPhpSdk\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LongRunningNodeLoader
 *
 * @package Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader
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

    /**
     * @param mixed[] $exclude
     *
     * @return mixed[]
     */
    public function getAllLongRunningNodes(array $exclude = []): array
    {
        $dirs = $this->container->getParameter('node_services_dirs');

        return NodeServiceLoader::getServices($dirs, self::PREFIX, $exclude);
    }

}
