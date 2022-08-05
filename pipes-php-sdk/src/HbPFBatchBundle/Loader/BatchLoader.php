<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader;

use Hanaboso\CommonsBundle\Utils\NodeServiceLoader;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\PipesPhpSdk\Batch\Exception\BatchException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BatchLoader
 *
 * @package Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader
 */
final class BatchLoader
{

    private const BATCH_PREFIX = 'hbpf.batch';

    /**
     * BatchLoader constructor.
     *
     * @param ContainerInterface $container
     */
    function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param string $id
     *
     * @return BatchAbstract
     * @throws BatchException
     */
    public function getBatch(string $id): BatchAbstract
    {
        $name = sprintf('%s.%s', self::BATCH_PREFIX, $id);

        if ($this->container->has($name)) {
            /** @var BatchAbstract $conn */
            $conn = $this->container->get($name);
        } else {
            throw new BatchException(
                sprintf('Service for [%s] batch was not found', $id),
                BatchException::BATCH_SERVICE_NOT_FOUND,
            );
        }

        return $conn;
    }

    /**
     * @param string[] $exclude
     *
     * @return string[]
     */
    public function getAllBeaches(array $exclude = []): array
    {
        $dirs = $this->container->getParameter('node_services_dirs');

        return NodeServiceLoader::getServices($dirs, self::BATCH_PREFIX, $exclude);
    }

}
