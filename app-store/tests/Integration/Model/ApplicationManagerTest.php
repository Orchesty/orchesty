<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Model;

use Exception;
use Hanaboso\HbPFAppStore\Model\ApplicationManager;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use HbPFAppStoreTests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationManagerTest
 *
 * @package HbPFAppStoreTests\Integration\Model
 */
final class ApplicationManagerTest extends DatabaseTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @var ApplicationManager
     */
    private $applicationManager;

    /**
     * @throws Exception
     */
    public function testGetApp(): void
    {
        $this->applicationManager->getApplication('null');

        self::assertFake();
    }


    /**
     * @throws Exception
     */
    public function testGetInstalledApplications(): void
    {
        $this->createApp();
        $this->createApp();

        $installedApp = $this->applicationManager->getInstalledApplications('example1');

        self::assertEquals(2, count($installedApp));
    }

    /**
     * @throws Exception
     */
    public function testGetInstalledApplicationDetail(): void
    {
        $this->createApp();

        $this->applicationManager->getInstalledApplicationDetail('some app', 'example1');

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);
        $this->applicationManager->getInstalledApplicationDetail('some app', 'example5');
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::installApplication
     *
     * @throws Exception
     */
    public function testInstallApplication(): void
    {
        $this->applicationManager->installApplication('something', 'example3');

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        $app        = $repository->findOneBy(
            [
                ApplicationInstall::USER => 'example3',
                ApplicationInstall::KEY  => 'something',
            ]
        );

        self::assertIsObject($app);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::installApplication
     *
     * @throws Exception
     */
    public function testInstallApplicationTest(): void
    {
        $this->createApp('key', 'user');

        self::expectException(ApplicationInstallException::class);
        $this->applicationManager->installApplication('key', 'user');
    }

    /**
     * @throws Exception
     */
    public function testUninstallApplication(): void
    {
        $this->createApp('null');

        $this->applicationManager->uninstallApplication('null', 'example1');

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        $app        = $repository->findAll();

        self::assertEquals([], $app);
    }

    /**
     * @throws Exception
     */
    public function testApplicationPassword(): void
    {
        $this->createApp('null');

        $this->applicationManager->saveApplicationPassword('null', 'example1', 'password123');
        $repository = $this->dm->getRepository(ApplicationInstall::class);
        /** @var ApplicationInstall $app */
        $app = $repository->findOneBy(['key' => 'null']);

        self::assertEquals(
            'password123',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS]['password']
        );
    }

    /**
     * @throws Exception
     */
    public function testApplicationSettings(): void
    {
        $this->createApp('null');

        $this->applicationManager->saveApplicationSettings(
            'null',
            'example1',
            [
                'settings1' => 'some text',
                'settings2' => 'example2',
            ]
        );
        $repository = $this->dm->getRepository(ApplicationInstall::class);
        /** @var ApplicationInstall $app */
        $app = $repository->findOneBy(['key' => 'null']);

        self::assertEquals('some text', $app->getSettings()['form']['settings1']);
    }

    /**
     * @throws Exception
     */
    public function testGetSettingsFormValues(): void
    {
        $this->createApp('null');

        $this->applicationManager->saveApplicationSettings(
            'null',
            'example1',
            [
                'settings1' => 'data1',
                'settings2' => 'data2',
                'settings3' => 'secret',
            ]
        );
        $values = $this->applicationManager->getApplicationSettings('null', 'example1');

        self::assertEquals('settings1', $values[0]['key']);
        self::assertEquals(TRUE, $values[2]['value']);
    }

    /**
     * @throws Exception
     */
    public function testSetApplicationSettingForm(): void
    {
        $this->createApp('null');

        $this->applicationManager->saveApplicationSettings(
            'null',
            'example1',
            [
                'settings1' => 'data1',
                'settings2' => 'data2',
                'password'  => 'secret123',
            ]
        );

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        /** @var ApplicationInstall $app */
        $app = $repository->findOneBy(['key' => 'null']);

        self::assertEquals('data1', $app->getSettings()['form']['settings1']);
        self::assertArrayNotHasKey('password', $app->getSettings()['form']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::subscribeWebhooks
     *
     * @throws Exception
     */
    public function testSubscribeWebhooks(): void
    {
        $applicationInstall = $this->createApp('null', 'user');
        $this->applicationManager->subscribeWebhooks($applicationInstall);

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationManager = self::$container->get('hbpf._application.manager.application');
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApp(string $key = 'some app', string $user = 'example1'): ApplicationInstall
    {
        $app = new ApplicationInstall();
        $app->setKey($key);
        $app->setUser($user);

        $this->persistAndFlush($app);

        return $app;
    }

}
