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
