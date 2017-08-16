<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 10:51 AM
 */

namespace Tests\Unit\Connector;

use Hanaboso\PipesFramework\Commons\Authorization\Connectors\AuthorizationInterface;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loaders\AuthorizationLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class ConnectorLoaderTest
 *
 * @package Tests\Unit\Connector
 */
class AuthorizationLoaderTest extends KernelTestCaseAbstract
{

    /** @var  AuthorizationLoader */
    private $loader;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = $this->container->get('hbpf.loader.authorization');
    }

    /**
     * @covers AuthorizationLoader::getAuthorization()
     */
    public function testGetAuthorization(): void
    {
        $conn = $this->loader->getAuthorization('magento2.oauth');
        self::assertInstanceOf(AuthorizationInterface::class, $conn);
    }

    /**
     * @covers AuthorizationLoader::getAllAuthorizations()
     */
    public function testGetAllAuthorizations(): void
    {
        $exclude = ['magento2.oauth'];
        $conns   = $this->loader->getAllAuthorizations($exclude);

        self::assertNotEmpty($conns);
        self::assertFalse(in_array('hbpf.authorization.magento2.oauth', $conns));
    }

}