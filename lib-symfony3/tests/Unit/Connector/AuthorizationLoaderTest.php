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
     * @covers AuthorizationLoader::get()
     */
    public function testGetAuthorization(): void
    {
        $conn = $this->loader->get('magento2.oauth');
        self::assertInstanceOf(AuthorizationInterface::class, $conn);
    }

    /**
     * @covers AuthorizationLoader::getAuthorizationIDs()
     */
    public function testGetAllAuthorizationsIds(): void
    {
        $exclude = ['magento2.oauth'];
        $conns = $this->loader->getAuthorizationIDs($exclude);
        self::assertNotEmpty($conns);
        self::assertTrue(is_string($conns[0]));
        self::assertFalse(in_array('hbpf.authorization.magento2.oauth', $conns));
    }

    /**
     * @covers AuthorizationLoader::getAuthorizations()
     */
    public function testGetAllAuthorizations(): void
    {
        $conns = $this->loader->getAuthorizations();
        self::assertNotEmpty($conns);
        self::assertInstanceOf(AuthorizationInterface::class, $conns[0]);
    }

}