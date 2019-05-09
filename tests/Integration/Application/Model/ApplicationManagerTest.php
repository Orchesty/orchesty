<?php declare(strict_types=1);

namespace Tests\Integration\Application\Model;

use Exception;
use Hanaboso\PipesFramework\Application\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesFramework\Application\Model\ApplicationManager;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Loader\ApplicationLoader;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationManagerTest
 *
 * @package Tests\Integration\Application\Model
 */
final class ApplicationManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetApp(): void
    {
        $nullApp = self::createMock(BasicApplicationInterface::class);
        self::$container->set('hbpf.application.null', $nullApp);

        $applicationManager = new ApplicationManager($this->dm, new ApplicationLoader(self::$container));
        $app                = $applicationManager->getApplication('null');

        self::assertIsObject($app);
    }

    /**
     * @throws Exception
     */
    public function testGetApplications(): void
    {
        $applicationManager = new ApplicationManager($this->dm, new ApplicationLoader(self::$container));
        $applications       = $applicationManager->getApplications();

        self::assertIsArray($applications);
    }

    /**
     * @throws Exception
     */
    public function testGetInstalledApplications(): void
    {
        $this->createApp();
        $this->createApp();

        $applicationManager = new ApplicationManager($this->dm, new ApplicationLoader(self::$container));
        $installedApp       = $applicationManager->getInstalledApplications('example1');

        self::assertEquals(2, count($installedApp));
    }

    /**
     * @throws Exception
     */
    public function testGetInstalledApplicationDetail(): void
    {
        $this->createApp();

        $applicationManager = new ApplicationManager($this->dm, new ApplicationLoader(self::$container));
        $appDetail          = $applicationManager->getInstalledApplicationDetail('some app', 'example1');
        self::assertIsObject($appDetail);

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);
        $applicationManager->getInstalledApplicationDetail('some app', 'example5');
    }

    /**
     * @throws Exception
     */
    public function testInstallApplication(): void
    {
        $applicationManager = new ApplicationManager($this->dm, new ApplicationLoader(self::$container));
        $applicationManager->installApplication('something', 'example3');

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
        $this->createApp();

        $applicationManager = new ApplicationManager($this->dm, new ApplicationLoader(self::$container));
        $applicationManager->uninstallApplication('some app', 'example1');

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        $app        = $repository->findAll();

        self::assertEquals([], $app);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws Exception
     */
    private function createApp(string $key = 'some app', string $user = 'example1'): void
    {
        $app = new ApplicationInstall();
        $app->setKey($key);
        $app->setUser($user);

        $this->persistAndFlush($app);
    }

}
