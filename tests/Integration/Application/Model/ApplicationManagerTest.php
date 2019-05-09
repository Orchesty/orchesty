<?php declare(strict_types=1);

namespace Tests\Integration\Application\Model;

use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
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
     *
     */
    public function testGetApplications(): void
    {
        $applicationManager = new ApplicationManager($this->dm, self::$container);
        $applications       = $applicationManager->getApplications();

        self::assertIsArray($applications);
    }

    /**
     * @throws DateTimeException
     */
    public function testGetInstalledApplications(): void
    {
        $this->createApp();
        $this->createApp('some app1', 'example2');
        $this->createApp('some app2', 'example2');

        $applicationManager = new ApplicationManager($this->dm, self::$container);
        $installedApp       = $applicationManager->getInstalledApplications('example2');

        self::assertEquals(2, count($installedApp));
    }

    /**
     * @throws Exception
     */
    public function testGetInstalledApplicationDetail(): void
    {
        $this->createApp();

        $applicationManager = new ApplicationManager($this->dm, self::$container);
        $appDetail          = $applicationManager->getInstalledApplicationDetail('some app', 'example1');
        self::assertIsObject($appDetail);

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);
        $applicationManager->getInstalledApplicationDetail('some app', 'example5');

    }

    /**
     * @throws DateTimeException
     * @throws ApplicationInstallException
     */
    public function testInstallApplication(): void
    {
        $applicationManager = new ApplicationManager($this->dm, self::$container);
        $applicationManager->installApplication('something', 'example3');

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        $app        = $repository->findOneBy(['user' => 'example3', 'key' => 'something']);

        self::assertIsObject($app);
    }

    /**
     * @throws Exception
     */
    public function testUninstallApplication(): void
    {
        $this->createApp();

        $applicationManager = new ApplicationManager($this->dm, self::$container);
        $applicationManager->uninstallApplication('some app', 'example1');

        $repository = $this->dm->getRepository(ApplicationInstall::class);
        $app        = $repository->findAll();

        self::assertEquals([], $app);
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
