<?php declare(strict_types=1);

namespace Tests\Unit\HbPFAuthorizationBundle\Loader;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
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
final class AuthorizationLoaderTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var  AuthorizationLoader
     */
    private $loader;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $auth = new Authorization('magento2_auth');
        $auth->setToken(['password' => 'Password']);

        $repo = self::createMock(AuthorizationRepository::class);
        $repo->method('getInstalledKeys')->willReturn(['magento2_auth']);

        $dm = self::createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);
        $dm->method('persist')->willReturn(NULL);
        $dm->method('flush')->willReturn(NULL);

        $this->loader = new AuthorizationLoader(self::$container, $dm);
    }

    /**
     * @covers AuthorizationLoader::getAuthorization()
     * @throws Exception
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
     * @throws Exception
     */
    public function testGetAllAuthorizationsInfo(): void
    {
        $conns = $this->loader->getAllAuthorizationsInfo('http://localhost');
        self::assertNotEmpty($conns);

        /** @var array $magento2 */
        $magento2 = reset($conns);

        self::assertArrayHasKey('name', $magento2);
        self::assertArrayHasKey('description', $magento2);
        self::assertArrayHasKey('type', $magento2);
        self::assertArrayHasKey('key', $magento2);
        self::assertArrayHasKey('is_authorized', $magento2);

        self::assertEquals('magento2 Authorization', $magento2['name']);
        self::assertEquals('magento2 Authorization', $magento2['description']);
        self::assertEquals('basic', $magento2['type']);
        self::assertEquals('magento2_auth', $magento2['key']);
    }

}
