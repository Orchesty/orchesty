<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 10:51 AM
 */

namespace Tests\Integration\HbPFAuthorizationBundle\Loader;

use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\Repository\AuthorizationRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class AuthorizationLoaderTest
 *
 * @package Tests\Integration\HbPFAuthorizationBundle\Loader
 */
class AuthorizationLoaderTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers AuthorizationLoader::getAuthorization()
     * @covers AuthorizationLoader::getInstalled()
     */
    public function testInstallAllAuthorizations(): void
    {
        $loader = $this->container->get('hbpf.loader.authorization');
        $dm     = $this->container->get('doctrine_mongodb.odm.default_document_manager');
        /** @var AuthorizationRepository $repo */
        $repo = $dm->getRepository(Authorization::class);

        $auth = new Authorization('magento2.auth');
        $this->persistAndFlush($auth);
        $dm->clear();

        $installed = $repo->getInstalledKeys();
        self::assertEquals(1, count($installed));
        self::assertEquals('magento2.auth', $installed[0]);

        $loader->installAllAuthorizations();
        $installed = $repo->getInstalledKeys();
        self::assertGreaterThan(1, count($installed));
        self::assertContains('magento2.oauth', $installed);
    }

}