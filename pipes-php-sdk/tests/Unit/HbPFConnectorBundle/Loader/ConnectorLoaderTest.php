<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ConnectorLoaderTest
 *
 * @package PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader
 */
#[CoversClass(ConnectorLoader::class)]
final class ConnectorLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var  ConnectorLoader
     */
    private ConnectorLoader $loader;

    /**
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
