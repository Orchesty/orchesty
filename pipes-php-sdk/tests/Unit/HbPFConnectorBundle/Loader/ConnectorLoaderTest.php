<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ConnectorLoaderTest
 *
 * @package PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader
 */
final class ConnectorLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var  ConnectorLoader
     */
    private ConnectorLoader $loader;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader::getConnector

     * @throws Exception
     */
    public function testGetConnector(): void
    {
        $conn = $this->loader->getConnector('null');
        self::assertInstanceOf(NullConnector::class, $conn);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = self::getContainer()->get('hbpf.loader.connector');
    }

}
