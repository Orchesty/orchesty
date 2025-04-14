<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader;

use Exception;
use Hanaboso\CommonsBundle\Utils\NodeServiceLoader;
use Hanaboso\PipesPhpSdk\Application\Document\Dto\CommonObjectDto;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\PipesPhpSdk\Batch\Exception\BatchException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

/**
 * Class BatchLoader
 *
 * @package Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader
 */
final class BatchLoader
{

    private const string BATCH_PREFIX = 'hbpf.batch';

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
    public function getAllBatches(array $exclude = []): array
    {
        $dirs = $this->container->getParameter('node_services_dirs');

        return NodeServiceLoader::getServices($dirs, self::BATCH_PREFIX, $exclude);
    }

    /**
     * @return CommonObjectDto[]
     */
    public function getList(): array
    {
        $services = array_map(function($serviceName) {
            try {
                return $this->getBatch($serviceName);
            } catch (Throwable) {
                return NULL;
            }
        }, self::getAllBatches());

        $services = array_filter($services);

        return array_map(static function ($batch) {

            try {
                $applicationName = $batch->getApplication()->getName();
            } catch (Exception) {
                $applicationName = NULL;
            }

            return new CommonObjectDto($batch->getName(), $applicationName);
        }, $services);
    }

}
