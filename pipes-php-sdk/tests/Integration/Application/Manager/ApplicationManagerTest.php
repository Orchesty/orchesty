<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Manager;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestOAuth2NullApplication;

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
    private $manager;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getApplications
     */
    public function testGetApplications(): void
    {
        self::assertEquals(['null', 'null2', 'null3'], $this->manager->getApplications());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::saveApplicationSettings
     *
     * @throws Exception
     */
    public function testSaveApplicationSettings(): void
    {
        $this->createApplicationInstall();

        $applicationInstall = $this->manager
            ->saveApplicationSettings('null', 'user', ['user' => 'user789']);

        self::assertEquals(
            'user789',
            $applicationInstall->getSettings()[ApplicationAbstract::FORM]['user']
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
        $applicationInstall = $this->manager->saveApplicationPassword('null', 'user', 'password123');

        self::assertEquals(
            ['password' => 'password123'],
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS]
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
            [ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::REDIRECT_URL => '/test/redirect']]
        );

        $app = self::createPartialMock(TestOAuth2NullApplication::class, ['setAuthorizationToken']);
        $app->expects(self::any())->method('setAuthorizationToken')->willReturnSelf();
        $loader = self::createPartialMock(ApplicationLoader::class, ['getApplication']);
        $loader->expects(self::any())->method('getApplication')->willReturn($app);
        $manager = new ApplicationManager($this->dm, $loader);

        self::assertEquals(
            ['redirect_url' => '/test/redirect'],
            $manager->saveAuthorizationToken('null2', 'user', ['code' => ['token']])
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::$container->get('hbpf.application.manager');
    }

    /**
     * @param string $key
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(string $key = 'null'): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setKey($key)
            ->setUser('user')
            ->setSettings(
                [ApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::PASSWORD => 'passwd987']]
            );
        $this->pfd($applicationInstall);

        return $applicationInstall;
    }

}
