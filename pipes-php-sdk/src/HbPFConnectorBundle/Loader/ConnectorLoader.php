<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader;

use Exception;
use Hanaboso\CommonsBundle\Utils\NodeServiceLoader;
use Hanaboso\PipesPhpSdk\Application\Document\Dto\CommonObjectDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

/**
 * Class ConnectorLoader
 *
 * @package Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader
 */
final class ConnectorLoader
{

    private const CONNECTOR_PREFIX = 'hbpf.connector';

    /**
     * ConnectorLoader constructor.
     *
     * @param ContainerInterface $container
     */
    function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param string $id
     *
     * @return ConnectorAbstract
     * @throws ConnectorException
     */
    public function getConnector(string $id): ConnectorAbstract
    {
        $name = sprintf('%s.%s', self::CONNECTOR_PREFIX, $id);

        if ($this->container->has($name)) {
            /** @var ConnectorAbstract $conn */
            $conn = $this->container->get($name);
        } else {
            throw new ConnectorException(
                sprintf('Service for [%s] connector was not found', $id),
                ConnectorException::CONNECTOR_SERVICE_NOT_FOUND,
            );
        }

        return $conn;
    }

    /**
     * @param string[] $exclude
     *
     * @return string[]
     */
    public function getAllConnectors(array $exclude = []): array
    {
        $dirs = $this->container->getParameter('node_services_dirs');

        return NodeServiceLoader::getServices($dirs, self::CONNECTOR_PREFIX, $exclude);
    }

    /**
     * @return CommonObjectDto[]
     */
    public function getList(): array {
        $services = array_map(function($serviceName) {
            try {
                return $this->getConnector($serviceName);
            } catch (Throwable) {
                return NULL;
            }
        }, self::getAllConnectors());

        $services = array_filter($services);

        return array_map(static function ($connector) {

            try {
                $applicationName = $connector->getApplication()->getName();
            }
            catch (Exception) {
                $applicationName = NULL;
            }

            return new CommonObjectDto($connector->getName(), $applicationName);
        }, $services);
    }

}
