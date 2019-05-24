<?php declare(strict_types=1);

namespace Tests\Integration\Application\Model;

use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesFramework\Application\Model\ApplicationManager;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationManagerTest
 *
 * @package Tests\Integration\Application\Model
 */
final class ApplicationManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var ApplicationManager
     */
    private $applicationManager;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationManager = self::$container->get('hbpf._application.manager.application');
    }

    /**
     * @throws Exception
     */
    public function testGetApp(): void
    {
        $app = $this->applicationManager->getApplication('null');

        self::assertIsObject($app);
    }

    /**
     * @throws Exception
     */
    public function testGetApplications(): void
    {
        $applications = $this->applicationManager->getApplications();

        self::assertIsArray($applications);
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

        $appDetail = $this->applicationManager->getInstalledApplicationDetail('some app', 'example1');
        self::assertIsObject($appDetail);

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);
        $this->applicationManager->getInstalledApplicationDetail('some app', 'example5');
    }

    /**
     * @throws Exception
     */
    public function testInstallApplication(): void
    {
        $this->applicationManager->installApplication('something', 'example3');

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        $app        = $repository->findOneBy([
            ApplicationInstall::USER => 'example3',
            ApplicationInstall::KEY  => 'something',
        ]);

        self::assertIsObject($app);
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
     * @throws DateTimeException
     */
    public function testApplicationPassword(): void
    {
        $this->createApp('null');

        $this->applicationManager->saveApplicationPassword('null', 'example1', 'password123');
        $repository = $this->dm->getRepository(ApplicationInstall::class);
        /** @var ApplicationInstall $app */
        $app = $repository->findOneBy(['key' => 'null']);

        self::assertEquals('password123',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS]['password']);
    }

    /**
     * @throws DateTimeException
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
     * @throws ApplicationInstallException
     * @throws DateTimeException
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
     * @throws DateTimeException
     */
    public function testSetApplicationSettingForm(): void
    {
        $this->createApp('null');

        $application = $this->applicationManager->saveApplicationSettings(
            'null',
            'example1',
            [
                'settings1' => 'data1',
                'settings2' => 'data2',
                'password'  => 'secret123',
            ]
        );

        self::assertIsObject($application);
        $repository = $this->dm->getRepository(ApplicationInstall::class);
        /** @var ApplicationInstall $app */
        $app = $repository->findOneBy(['key' => 'null']);

        self::assertEquals('data1', $app->getSettings()['form']['settings1']);
        self::assertArrayNotHasKey('password', $app->getSettings()['form']);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws DateTimeException
     */
    private function createApp(string $key = 'some app', string $user = 'example1'): void
    {
        $app = new ApplicationInstall();
        $app->setKey($key);
        $app->setUser($user);

        $this->persistAndFlush($app);
    }

}
