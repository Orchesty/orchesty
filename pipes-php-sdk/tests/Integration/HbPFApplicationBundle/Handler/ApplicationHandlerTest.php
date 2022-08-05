<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApplicationHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler
 */
final class ApplicationHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var ApplicationHandler
     */
    private ApplicationHandler $handler;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::getApplications
     *
     * @throws Exception
     */
    public function testGetApplications(): void
    {
        $ex = [
            'items' => [
                [
                    'key'                => 'null-key',
                    'name'               => 'Null',
                    'authorization_type' => 'basic',
                    'application_type'   => 'cron',
                    'description'        => 'Application for test purposes',
                ],
                [
                    'key'                => 'null2',
                    'name'               => 'Null2',
                    'authorization_type' => 'oauth2',
                    'application_type'   => 'cron',
                    'description'        => 'Application for test purposes',
                ],
                [
                    'key'                => 'null1',
                    'name'               => 'null1',
                    'authorization_type' => 'oauth',
                    'application_type'   => 'webhook',
                    'description'        => 'This is null ouath1 app.',
                ],
            ],
        ];
        self::assertEquals($ex, $this->handler->getApplications());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::getApplications
     *
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        self::assertEquals(
            [
                'key'                => 'null-key',
                'name'               => 'Null',
                'authorization_type' => 'basic',
                'application_type'   => 'cron',
                'description'        => 'Application for test purposes',
                'syncMethods'        => ['testSynchronous', 'returnBody'],
            ],
            $this->handler->getApplicationByKey('null'),
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::getSynchronousActions
     *
     * @throws Exception
     */
    public function testGetSynchronousActions(): void
    {
        self::assertEquals(['testSynchronous', 'returnBody'], $this->handler->getSynchronousActions('null'));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::runSynchronousAction
     *
     * @throws Exception
     */
    public function testRunSynchronousAction(): void
    {
        $r = new Request([]);
        $r->setMethod(CurlManager::METHOD_GET);

        self::assertEquals(
            'ok',
            $this->handler->runSynchronousAction('null', 'testSynchronous', $r),
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::authorizeApplication
     *
     * @throws Exception
     */
    public function testAuthorizeApplication(): void
    {
        $this->createApplicationInstall();
        $manager = self::createPartialMock(ApplicationManager::class, ['authorizeApplication']);
        $manager->expects(self::any())->method('authorizeApplication');

        $handler = new ApplicationHandler($manager);
        $handler->authorizeApplication('null', 'user', '/redirect/url');
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::saveAuthToken
     *
     * @throws Exception
     */
    public function testSaveAuthToken(): void
    {
        $this->createApplicationInstall();
        $manager = self::createPartialMock(ApplicationManager::class, ['authorizeApplication']);
        $manager->expects(self::any())->method('authorizeApplication');

        $handler = new ApplicationHandler($manager);
        $handler->authorizeApplication('null', 'user', '/redirect/url');
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::saveAuthToken
     *
     * @throws Exception
     */
    public function testAuthToken(): void
    {
        $manager = self::createPartialMock(ApplicationManager::class, ['saveAuthorizationToken']);
        $manager
            ->expects(self::any())->method('saveAuthorizationToken')
            ->willReturn('/redirect/url');

        $handler     = new ApplicationHandler($manager);
        $redirectUrl = $handler->saveAuthToken('null', 'user', ['code' => '__code__']);

        self::assertEquals('/redirect/url', $redirectUrl);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::getContainer()->get('hbpf.application.handler');
    }

    /**
     * @throws Exception
     */
    private function createApplicationInstall(): void
    {
        $applicationInstall = (new ApplicationInstall())
            ->setKey('null')
            ->setUser('user')
            ->setSettings([]);

        $this->pfd($applicationInstall);
    }

}
