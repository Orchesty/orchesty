<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager as ApplicationManagerAlias;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler;
use InvalidArgumentException;
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
                    'info'               => '',
                    'logo'               => NULL,
                    'isInstallable'      => TRUE,

                ],
                [
                    'key'                => 'null2',
                    'name'               => 'Null2',
                    'authorization_type' => 'oauth2',
                    'application_type'   => 'cron',
                    'description'        => 'Application for test purposes',
                    'info'               => '',
                    'logo'               => NULL,
                    'isInstallable'      => TRUE,
                ],
                [
                    'key'                => 'null1',
                    'name'               => 'null1',
                    'authorization_type' => 'oauth',
                    'application_type'   => 'webhook',
                    'description'        => 'This is null ouath1 app.',
                    'info'               => '',
                    'logo'               => NULL,
                    'isInstallable'      => TRUE,
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
                'info'               => '',
                'logo'               => NULL,
                'isInstallable'      => TRUE,
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
        $applicationManager = self::createPartialMock(ApplicationManager::class, ['authorizeApplication']);
        $applicationManager->expects(self::any())->method('authorizeApplication');

        $webhookManager = self::createMock(WebhookManager::class);

        $handler = new ApplicationHandler($applicationManager, $webhookManager);
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
        $applicationManager = self::createPartialMock(ApplicationManager::class, ['authorizeApplication']);
        $applicationManager->expects(self::any())->method('authorizeApplication');

        $webhookManager = self::createMock(WebhookManager::class);

        $handler = new ApplicationHandler($applicationManager, $webhookManager);
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
        $applicationManager = self::createPartialMock(ApplicationManager::class, ['saveAuthorizationToken']);
        $applicationManager
            ->expects(self::any())->method('saveAuthorizationToken')
            ->willReturn('/redirect/url');

        $webhookManager = self::createMock(WebhookManager::class);

        $handler     = new ApplicationHandler($applicationManager, $webhookManager);
        $redirectUrl = $handler->saveAuthToken('null', 'user', ['code' => '__code__']);

        self::assertEquals('/redirect/url', $redirectUrl);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::getApplicationsByUser
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getApplication
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getInstalledApplications
     *
     * @throws Exception
     */
    public function testGetApplicationsByUser(): void
    {
        $this->createApplicationInstall('null');
        $this->createApplicationInstall('webhook');
        $result = $this->handler->getApplicationsByUser('user');

        self::assertEquals(2, count($result['items']));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::getApplicationByKeyAndUser
     *
     * @throws Exception
     */
    public function testGetApplicationByKeyAndUser(): void
    {
        $this->createApplicationInstall('webhook');

        $result = $this->handler->getApplicationByKeyAndUser('webhook', 'user');
        self::assertEquals('Webhook', $result['name']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::updateApplicationSettings
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::saveApplicationSettings
     *
     * @throws Exception
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->createApplicationInstall(
            'null',
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::USER => 'Old user',
                    BasicApplicationInterface::PASSWORD => 'Old password',
                ],
            ],
        );
        $res = $this->handler->updateApplicationSettings(
            'null',
            'user',
            [ApplicationInterface::AUTHORIZATION_FORM => [BasicApplicationInterface::USER => 'New user']],
        );

        self::assertEquals(
            'New user',
            $res[ApplicationManagerAlias::APPLICATION_SETTINGS][ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS][0]['value'],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::updateApplicationPassword
     *
     * @throws Exception
     */
    public function testUpdateApplicationPassword(): void
    {
        $this->createApplicationInstall('null');

        $this->handler->updateApplicationPassword(
            'null',
            'user',
            [
                'formKey' => ApplicationInterface::AUTHORIZATION_FORM,
                'fieldKey' => BasicApplicationInterface::PASSWORD,
                'password' => '_newPasswd_',
            ],
        );
        $app = $this->handler->getApplicationByKeyAndUser('null', 'user');
        self::assertEquals(
            TRUE,
            $app[ApplicationManager::APPLICATION_SETTINGS][ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS][1]['value'],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::updateApplicationPassword
     *
     * @throws Exception
     */
    public function testUpdateApplicationPasswordErr(): void
    {
        $this->createApplicationInstall('null');

        self::expectException(InvalidArgumentException::class);
        $this->handler->updateApplicationPassword(
            'null',
            'user',
            [
                'formKey' => ApplicationInterface::AUTHORIZATION_FORM,
                'fieldKey' => BasicApplicationInterface::PASSWORD,
                'username' => 'newUsername',
            ],
        );
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
     * @param string  $key
     * @param mixed[] $settings
     *
     * @return void
     * @throws Exception
     */
    private function createApplicationInstall(string $key = 'key', array $settings = []): void
    {
        $applicationInstall = (new ApplicationInstall())
            ->setKey($key)
            ->setUser('user')
            ->setSettings($settings);

        $this->pfd($applicationInstall);
    }

}
