<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Loader;

use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\Utils\NodeServiceLoaderUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConnectorLoader
 *
 * @package Hanaboso\PipesFramework\HbPFConnectorBundle\Loader
 */
class ConnectorLoader
{

    private const CONNECTOR_PREFIX = 'hbpf.connector';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ConnectorLoader constructor.
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
     * @return ConnectorInterface
     * @throws ConnectorException
     */
    public function getConnector(string $id): ConnectorInterface
    {
        $name = sprintf('%s.%s', self::CONNECTOR_PREFIX, $id);

        if ($this->container->has($name)) {
            /** @var ConnectorInterface $conn */
            $conn = $this->container->get($name);
        } else {
            throw new ConnectorException(
                sprintf('Service for [%s] connector was not found', $id),
                ConnectorException::CONNECTOR_SERVICE_NOT_FOUND
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

        return NodeServiceLoaderUtil::getServices($dirs, self::CONNECTOR_PREFIX, $exclude);
    }

}