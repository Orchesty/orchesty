<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Loader;

use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

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
        $list = Yaml::parse((string) file_get_contents(__DIR__ . '/../Resources/config/connectors.yml'));
        $res  = [];

        foreach (array_keys($list['services']) as $key) {
            $shortened = str_replace(sprintf('%s.', self::CONNECTOR_PREFIX), '', (string) $key);
            if (in_array($shortened, $exclude)) {
                unset($exclude[$shortened]);
                continue;
            }
            $res[] = $shortened;
        }

        return $res;
    }

}
