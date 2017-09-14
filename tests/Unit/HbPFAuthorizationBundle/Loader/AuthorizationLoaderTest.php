<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 10:51 AM
 */

namespace Tests\Unit\HbPFAuthorizationBundle\Loader;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorization\Base\AuthorizationInterface;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\Repository\AuthorizationRepository;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Loader\AuthorizationLoader;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class AuthorizationLoaderTest
 *
 * @package Tests\Unit\HbPFAuthorizationBundle\Loader
 */
class AuthorizationLoaderTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var  AuthorizationLoader
     */
    private $loader;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $crypt = $this->container->get('hbpf.crypt.crypt_manager');
        $auth  = new Authorization('magento2.auth');
        $auth->setEncryptedToken($crypt->encrypt('Password'));

        $repo = $this->createPartialMock(AuthorizationRepository::class, ['getInstalledKeys']);
        $repo->method('getInstalledKeys')->willReturn(['magento2.auth']);

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
        $conn = $this->loader->getAuthorization('magento2_oauth');
        self::assertInstanceOf(AuthorizationInterface::class, $conn);
    }

    /**
     * @covers AuthorizationLoader::getAllAuthorizations()
     */
    public function testGetAllAuthorizations(): void
    {
        $exclude = ['magento2_oauth'];
        $conns   = $this->loader->getAllAuthorizations($exclude);

        self::assertNotEmpty($conns);
        self::assertFalse(in_array('magento2_oauth', $conns));
    }

    /**
     * @covers AuthorizationLoader::getAllAuthorizations()
     */
    public function testGetAllAuthorizationsInfo(): void
    {
        $conns = $this->loader->getAllAuthorizationsInfo();
        self::assertNotEmpty($conns);

        /** @var array $magento2 */
        $magento2 = $conns['magento2.auth'];

        self::assertArrayHasKey('name', $magento2);
        self::assertArrayHasKey('description', $magento2);
        self::assertArrayHasKey('type', $magento2);
        self::assertArrayHasKey('is_authorized', $magento2);

        self::assertEquals('magento2 Authorization', $magento2['name']);
        self::assertEquals('magento2 Authorization', $magento2['description']);
        self::assertEquals('basic', $magento2['type']);
    }

}