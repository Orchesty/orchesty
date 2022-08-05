<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Model;

use Exception;
use Hanaboso\HbPFAppStore\Model\ApplicationManager;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
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
    private ApplicationManager $applicationManager;

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
            ],
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

        $this->applicationManager->saveApplicationPassword(
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
        $this->createApp('null');

        $this->applicationManager->saveApplicationSettings(
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
        $this->createApp('null');

        $this->applicationManager->saveApplicationSettings(
            'null',
            'example1',
            [ApplicationInterface::AUTHORIZATION_FORM => [
                BasicApplicationInterface::USER => 'data1',
                BasicApplicationInterface::PASSWORD => 'data2',
                'settings3' => 'secret',
                ],
            ],
        );
        $values = $this->applicationManager->getApplicationSettings('null', 'example1');

        self::assertEquals(
            BasicApplicationInterface::USER,
            $values[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS][0]['key'],
        );
        self::assertEquals(
            TRUE,
            $values[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS][2]['value'],
        );
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

        $this->applicationManager = self::getContainer()->get('hbpf._application.manager.application');
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
