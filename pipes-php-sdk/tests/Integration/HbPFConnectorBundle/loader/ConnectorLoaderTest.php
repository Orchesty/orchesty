<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFConnectorBundle\loader;

use Exception;
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
        $connector = new ConnectorLoader(self::getContainer());

        $fields = $connector->getAllConnectors();
        self::assertCount(1, $fields);

        $fields = $connector->getAllConnectors(['null']);
        self::assertCount(0, $fields);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader::getConnector
     *
     * @throws Exception
     */
    public function testGetConnector(): void
    {
        $connector = new ConnectorLoader(self::getContainer());

        self::expectException(ConnectorException::class);
        $connector->getConnector('null1');
    }

}
