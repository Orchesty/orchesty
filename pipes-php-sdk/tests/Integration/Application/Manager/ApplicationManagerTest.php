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
            'testPublicName',
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
        $manager = new ApplicationManager($this->dm, $loader, $reader);

        self::assertEquals(
            '/test/redirect',
            $manager->saveAuthorizationToken('null2', 'user', ['code' => ['token']]),
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::getContainer()->get('hbpf.application.manager');
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
            ->setSettings(['applicationSettings' => ['test' => ['a' => 'aValue']]]);

        $this->pfd($applicationInstall);

        return $applicationInstall;
    }

}
