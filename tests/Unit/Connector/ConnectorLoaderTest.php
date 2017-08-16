<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 10:51 AM
 */

namespace Tests\Unit\Connector;

use Hanaboso\PipesFramework\Commons\Node\BaseNode;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loaders\ConnectorLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class ConnectorLoaderTest
 *
 * @package Tests\Unit\Connector
 */
class ConnectorLoaderTest extends KernelTestCaseAbstract
{

    /** @var  ConnectorLoader */
    private $loader;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = $this->container->get('hbpf.loader.connector');
    }

    /**
     * @covers ConnectorLoader::getConnector()
     */
    public function testGetConnector(): void
    {
        $conn = $this->loader->getConnector('magento2', 'orders');
        self::assertInstanceOf(BaseNode::class, $conn);
    }

    /**
     * @covers ConnectorLoader::getAllConnectors()
     */
    public function testGetAllConnector(): void
    {
        $exclude = ['magento2.modules'];
        $conns = $this->loader->getAllConnectors($exclude);

        self::assertNotEmpty($conns);
        self::assertFalse(in_array('hbpf.connector.magento2.modules', $conns));
    }

}