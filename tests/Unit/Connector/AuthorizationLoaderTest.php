<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 10:51 AM
 */

namespace Tests\Unit\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorizations\Document\Authorization;
use Hanaboso\PipesFramework\Authorizations\Repository\AuthorizationRepository;
use Hanaboso\PipesFramework\Commons\Authorization\Connectors\AuthorizationInterface;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loaders\AuthorizationLoader;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class ConnectorLoaderTest
 *
 * @package Tests\Unit\Connector
 */
class AuthorizationLoaderTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /** @var  AuthorizationLoader */
    private $loader;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $auth = new Authorization('magento2.auth');

        $repo = $this->createPartialMock(AuthorizationRepository::class, ['findAll']);
        $repo->method('findAll')->willReturn([$auth]);

        $dm = $this->createPartialMock(DocumentManager::class, ['getRepository']);
        $dm->method('getRepository')->willReturn($repo);

        $this->loader = $this->container->get('hbpf.loader.authorization');
        $this->setProperty($this->loader, 'dm', $dm);
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
        self::assertFalse(in_array('magento2.oauth', $conns));
    }

    /**
     * @covers AuthorizationLoader::getAllAuthorizations()
     */
    public function testGetAllAuthorizationsInfo(): void
    {
        $conns = $this->loader->getAllAuthorizationsInfo();

        $expect = [
            'name'          => 'magento2 - auth',
            'description'   => 'magento2 - auth',
            'type'          => 'basic',
            'is_authorized' => FALSE,
        ];

        self::assertNotEmpty($conns);
        self::assertEquals($expect, $conns['magento2.auth']);
    }

}