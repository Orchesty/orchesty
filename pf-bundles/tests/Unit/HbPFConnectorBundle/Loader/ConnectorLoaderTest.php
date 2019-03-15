<?php declare(strict_types=1);

namespace Tests\Unit\HbPFConnectorBundle\Loader;

use Exception;
use Hanaboso\PipesFramework\Connector\Impl\Magento2\Magento2OrdersConnector;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loader\ConnectorLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class ConnectorLoaderTest
 *
 * @package Tests\Unit\HbPFConnectorBundle\Loader
 */
final class ConnectorLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var  ConnectorLoader
     */
    private $loader;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = $this->ownContainer->get('hbpf.loader.connector');
    }

    /**
     * @covers ConnectorLoader::getConnector()
     * @throws Exception
     */
    public function testGetConnector(): void
    {
        $conn = $this->loader->getConnector('magento2.orders');
        self::assertInstanceOf(Magento2OrdersConnector::class, $conn);
    }

    /**
     * @covers ConnectorLoader::getAllConnectors()
     */
    public function testGetAllConnector(): void
    {
        $exclude = ['magento2.modules'];
        $conns   = $this->loader->getAllConnectors($exclude);

        self::assertNotEmpty($conns);
        self::assertFalse(in_array('magento2.modules', $conns));
    }

}
