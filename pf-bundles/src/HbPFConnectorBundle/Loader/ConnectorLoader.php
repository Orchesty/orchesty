<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 10:37 AM
 */

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Loader;

use Hanaboso\PipesFramework\Commons\Node\BaseNode;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Exception\ConnectorException;
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
     * @param string $system
     * @param string $endpoint
     *
     * @return BaseNode
     * @throws ConnectorException
     */
    public function getConnector(string $system, string $endpoint): BaseNode
    {
        $name = sprintf('%s.%s.%s', self::CONNECTOR_PREFIX, $system, $endpoint);

        if ($this->container->has($name)) {
            /** @var BaseNode $conn */
            $conn = $this->container->get($name);
        } else {
            throw new ConnectorException(
                sprintf('Service for [%s, %s] connector was not found', $system, $endpoint),
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
        $list = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/config/connectors.yml'));
        $res = [];

        foreach ($list['services'] as $key => $item) {
            $shortened = str_replace(self::CONNECTOR_PREFIX . '.', '', $key);
            if (in_array($shortened, $exclude)) {
                unset($exclude[$shortened]);
                continue;
            }
            $res[] = $shortened;
        }

        return $res;
    }

}