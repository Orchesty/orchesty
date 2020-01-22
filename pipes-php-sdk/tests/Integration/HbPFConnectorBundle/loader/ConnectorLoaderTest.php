<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFConnectorBundle\loader;

use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ConnectorLoaderTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFConnectorBundle\loader
 */
final class ConnectorLoaderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetAllConnectors(): void
    {
        $connector = new ConnectorLoader(self::$container);

        $fields = $connector->getAllConnectors();
        self::assertCount(2, $fields);

        $fields = $connector->getAllConnectors(['null']);
        self::assertCount(1, $fields);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader::getConnector
     *
     * @throws ConnectorException
     */
    public function testGetConnector(): void
    {
        $connector = new ConnectorLoader(self::$container);

        self::expectException(ConnectorException::class);
        $connector->getConnector('null1');
    }

}
