<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestOAuth2NullApplication;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApplicationManagerTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Manager
 */
final class ApplicationManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var ApplicationManager
     */
    private ApplicationManager $manager;

    /**
     * @var WebhookManager
     */
    private WebhookManager $webhookManager;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getApplications
     */
    public function testGetApplications(): void
    {
        self::assertEquals(['null', 'null2', 'null3'], $this->manager->getApplications());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getApplication
     *
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        self::assertEquals('null-key', $this->manager->getApplication('null')->getName());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getSynchronousActions
     *
     * @throws Exception
     */
    public function testGetSynchronousActions(): void
    {
        self::assertEquals(['testSynchronous', 'returnBody'], $this->manager->getSynchronousActions('null'));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::runSynchronousAction
     *
     * @throws Exception
     */
    public function testRunSynchronousAction(): void
    {
        $r = new Request([]);
        $r->setMethod(CurlManager::METHOD_GET);

        self::assertEquals(
            'ok',
            $this->manager->runSynchronousAction('null', 'testSynchronous', $r),
        );

        $r = new Request([], ['data']);
        $r->setMethod(CurlManager::METHOD_POST);

        self::assertEquals(
            ['data'],
            $this->manager->runSynchronousAction('null', 'returnBody', $r),
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::runSynchronousAction
     *
     * @throws Exception
     */
    public function testRunSynchronousActionException(): void
    {
        $r = new Request([]);
        $r->setMethod(CurlManager::METHOD_GET);

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::METHOD_NOT_FOUND);
        $this->manager->runSynchronousAction('null', 'notExist', $r);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::saveApplicationSettings
     *
     * @throws Exception
     */
    public function testSaveApplicationSettings(): void
    {
        $this->createApplicationInstall();

        $res = $this->manager
            ->saveApplicationSettings(
                'null',
                'user',
                ['test' => ['b' => 'bValue']],
            );

        $this->dm->clear();

        self::assertEquals(
            'authorization_form',
            $res[ApplicationManager::APPLICATION_SETTINGS][ApplicationInterface::AUTHORIZATION_FORM]['key'],
        );

        self::assertEquals(
            'testPublicName',
            $res[ApplicationManager::APPLICATION_SETTINGS][ApplicationInterface::AUTHORIZATION_FORM]['publicName'],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::saveApplicationPassword
     *
     * @throws Exception
     */
    public function testSaveApplicationPassword(): void
    {
        $this->createApplicationInstall();
        $applicationInstall = $this->manager->saveApplicationPassword(
            'null',
            'user',
            ApplicationInterface::AUTHORIZATION_FORM,
            BasicApplicationInterface::PASSWORD,
            'password123',
        );

        self::assertEquals(
            'password123',
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::saveAuthorizationToken
     *
     * @throws Exception
     */
    public function testSaveAuthorizationToken(): void
    {
        $applicationInstall = $this->createApplicationInstall('null2');
        $applicationInstall->setSettings(
            [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::FRONTEND_REDIRECT_URL => '/test/redirect']],
        );

        $app = self::createPartialMock(TestOAuth2NullApplication::class, ['setAuthorizationToken']);
        $app->expects(self::any())->method('setAuthorizationToken')->willReturnSelf();
        $loader = self::createPartialMock(ApplicationLoader::class, ['getApplication']);
        $loader->expects(self::any())->method('getApplication')->willReturn($app);
        $reader  = new PsrCachedReader(new AnnotationReader(), new ApcuAdapter());
        $manager = new ApplicationManager($this->dm, $loader, $reader, $this->webhookManager);

        self::assertEquals(
            '/test/redirect',
            $manager->saveAuthorizationToken('null2', 'user', ['code' => ['token']]),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetInstalledApplications(): void
    {
        $this->createApplicationInstall();
        $this->createApplicationInstall();

        $installedApp = $this->manager->getInstalledApplications('user');

        self::assertEquals(2, count($installedApp));
    }

    /**
     * @throws Exception
     */
    public function testGetInstalledApplicationDetail(): void
    {
        $this->createApplicationInstall('some app', 'example1');

        $this->manager->getInstalledApplicationDetail('some app', 'example1');

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);
        $this->manager->getInstalledApplicationDetail('some app', 'example5');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::installApplication
     *
     * @throws Exception
     */
    public function testInstallApplication(): void
    {
        $this->manager->installApplication('something', 'example3');

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        $app        = $repository->findOneBy(
            [
                ApplicationInstall::USER => 'example3',
                ApplicationInstall::KEY  => 'something',
            ],
        );

        self::assertIsObject($app);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::installApplication
     *
     * @throws Exception
     */
    public function testInstallApplicationTest(): void
    {
        $this->createApplicationInstall('key');

        self::expectException(ApplicationInstallException::class);
        $this->manager->installApplication('key', 'user');
    }

    /**
     * @throws Exception
     */
    public function testUninstallApplication(): void
    {
        $this->createApplicationInstall('null', 'example1');

        $this->manager->uninstallApplication('null', 'example1');

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        $app        = $repository->findAll();

        self::assertEquals([], $app);
    }

    /**
     * @throws Exception
     */
    public function testApplicationPassword(): void
    {
        $this->createApplicationInstall('null', 'example1');

        $this->manager->saveApplicationPassword(
            'null',
            'example1',
            ApplicationInterface::AUTHORIZATION_FORM,
            BasicApplicationInterface::PASSWORD,
            'password123',
        );
        $repository = $this->dm->getRepository(ApplicationInstall::class);
        /** @var ApplicationInstall $app */
        $app = $repository->findOneBy(['key' => 'null']);

        self::assertEquals(
            'password123',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @throws Exception
     */
    public function testApplicationSettings(): void
    {
        $this->createApplicationInstall('null', 'example1');

        $this->manager->saveApplicationSettings(
            'null',
            'example1',
            [ApplicationInterface::AUTHORIZATION_FORM => [
                BasicApplicationInterface::USER => 'testUser',
                BasicApplicationInterface::PASSWORD => 'testPass',
            ],
            ],
        );
        $repository = $this->dm->getRepository(ApplicationInstall::class);
        /** @var ApplicationInstall $app */
        $app = $repository->findOneBy(['key' => 'null']);

        self::assertEquals(
            'testUser',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER],
        );

        self::assertEquals(
            'testPass',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetSettingsFormValues(): void
    {
        $this->createApplicationInstall('null', 'example1');

        $this->manager->saveApplicationSettings(
            'null',
            'example1',
            [ApplicationInterface::AUTHORIZATION_FORM => [
                BasicApplicationInterface::USER => 'data1',
                BasicApplicationInterface::PASSWORD => 'data2',
                'settings3' => 'secret',
            ],
            ],
        );
        $values = $this->manager->getApplicationSettings('null', 'example1');

        self::assertEquals(
            BasicApplicationInterface::USER,
            $values[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS][0]['key'],
        );
        self::assertEquals(
            'data1',
            $values[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS][0]['value'],
        );
    }

    /**
     * @throws Exception
     */
    public function testSetApplicationSettingForm(): void
    {
        $this->createApplicationInstall('null', 'example1');

        $this->manager->saveApplicationSettings(
            'null',
            'example1',
            [ApplicationInterface::AUTHORIZATION_FORM => [
                BasicApplicationInterface::USER => 'data1',
                BasicApplicationInterface::PASSWORD => 'data2',
            ],
            ],
        );

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        /** @var ApplicationInstall $app */
        $app = $repository->findOneBy(['key' => 'null']);

        self::assertEquals(
            'data1',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER],
        );
        self::assertEquals(
            'data2',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::subscribeWebhooks
     *
     * @throws Exception
     */
    public function testSubscribeWebhooks(): void
    {
        $applicationInstall = $this->createApplicationInstall('null', 'user');
        $this->manager->subscribeWebhooks($applicationInstall);

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager        = self::getContainer()->get('hbpf.application.manager');
        $this->webhookManager = self::getContainer()->get('hbpf.application.manager.webhook');
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(string $key = 'null', string $user = 'user'): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setKey($key)
            ->setUser($user)
            ->setSettings(['applicationSettings' => ['test' => ['a' => 'aValue']]]);

        $this->pfd($applicationInstall);

        return $applicationInstall;
    }

}
