<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager as ApplicationManagerAlias;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler;
use Hanaboso\Utils\String\Json;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApplicationHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler
 */
#[CoversClass(ApplicationHandler::class)]
#[CoversClass(ApplicationManager::class)]
final class ApplicationHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @var ApplicationHandler
     */
    private ApplicationHandler $handler;

    /**
     * @throws Exception
     */
    public function testGetApplications(): void
    {
        $this->privateSetUp();
        $ex = [
            'items' => [
                [
                    'application_type'   => 'cron',
                    'authorization_type' => 'basic',
                    'description'        => 'Application for test purposes',
                    'info'               => '',
                    'isInstallable'      => TRUE,
                    'key'                => 'null-key',
                    'logo'               => NULL,
                    'name'               => 'Null',

                ],
                [
                    'application_type'   => 'cron',
                    'authorization_type' => 'oauth2',
                    'description'        => 'Application for test purposes',
                    'info'               => '',
                    'isInstallable'      => TRUE,
                    'key'                => 'null2',
                    'logo'               => NULL,
                    'name'               => 'Null2',
                ],
                [
                    'application_type'   => 'webhook',
                    'authorization_type' => 'oauth',
                    'description'        => 'This is null ouath1 app.',
                    'info'               => '',
                    'isInstallable'      => TRUE,
                    'key'                => 'null1',
                    'logo'               => NULL,
                    'name'               => 'null1',
                ],
            ],
        ];
        self::assertEquals($ex, $this->handler->getApplications());
    }

    /**
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $this->privateSetUp();
        self::assertEquals(
            [
                'application_type'   => 'cron',
                'authorization_type' => 'basic',
                'description'        => 'Application for test purposes',
                'info'               => '',
                'isInstallable'      => TRUE,
                'key'                => 'null-key',
                'logo'               => NULL,
                'name'               => 'Null',
                'syncMethods'        => ['testSynchronous', 'returnBody'],
            ],
            $this->handler->getApplicationByKey('null'),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetSynchronousActions(): void
    {
        $this->privateSetUp();
        self::assertEquals(['testSynchronous', 'returnBody'], $this->handler->getSynchronousActions('null'));
    }

    /**
     * @throws Exception
     */
    public function testRunSynchronousAction(): void
    {
        $this->privateSetUp();
        $r = new Request([]);
        $r->setMethod(CurlManager::METHOD_GET);

        self::assertEquals(
            'ok',
            $this->handler->runSynchronousAction('null', 'testSynchronous', $r),
        );
    }

    /**
     * @throws Exception
     */
    public function testAuthorizeApplication(): void
    {
        $applicationManager = self::createPartialMock(ApplicationManager::class, ['authorizeApplication']);
        $applicationManager->expects(self::any())->method('authorizeApplication');

        $webhookManager = self::createMock(WebhookManager::class);

        $handler = new ApplicationHandler($applicationManager, $webhookManager);
        $handler->authorizeApplication('null', 'user', '/redirect/url');
        self::assertFake();
    }

    /**
     * @throws Exception
     */
    public function testSaveAuthToken(): void
    {
        $applicationManager = self::createPartialMock(ApplicationManager::class, ['authorizeApplication']);
        $applicationManager->expects(self::any())->method('authorizeApplication');

        $webhookManager = self::createMock(WebhookManager::class);

        $handler = new ApplicationHandler($applicationManager, $webhookManager);
        $handler->authorizeApplication('null', 'user', '/redirect/url');
        self::assertFake();
    }

    /**
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

        self::assertSame('/redirect/url', $redirectUrl);
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testGetApplicationsByUser(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{"user":"user","name":"null"}, {"user":"user","name":"webhook"}]'),
            ),
        );
        $this->privateSetUp();
        $result = $this->handler->getApplicationsByUser('user');

        self::assertEquals(2, count($result['items']));
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationByKeyAndUser(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["webhook"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{"name":"webhook"}]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook?filter={"applications":["webhook"],"user_uds":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[]'),
            ),
        );
        $this->privateSetUp();
        $result = $this->handler->getApplicationByKeyAndUser('webhook', 'user');
        self::assertEquals('Webhook', $result['name']);
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{"name":"null"}]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":null,"name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_nobgP7JUTYhsadJ56IPtuX03bUDbPwmAtl7MkPifJUs=:wL6pmknQU9qKjTLg2Aao49RswmMyt28\/8r\/KeWGAYWU=:RHlWFtL4HtAmh0420xuugb3Z\/AT+\/e5Y:8uF+I3CNSdsDreN0vHE1pkIA\/2h\/ddLXhg4guLU6h1U+oc6RvPES88E8a9QKRVPrIECgRNOxtgwE3GvpjU6vUZOm7v03iBHatqYWmSFu1sU=","settings":[],"created":"2023-02-13 13:21:23","updated":"2023-02-13 13:21:23","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '[]'),
                [
                    'created'           => '2023-02-13 13:21:23',
                    'encryptedSettings' => '001_nobgP7JUTYhsadJ56IPtuX03bUDbPwmAtl7MkPifJUs=:wL6pmknQU9qKjTLg2Aao49RswmMyt28/8r/KeWGAYWU=:RHlWFtL4HtAmh0420xuugb3Z/AT+/e5Y:8uF+I3CNSdsDreN0vHE1pkIA/2h/ddLXhg4guLU6h1U+oc6RvPES88E8a9QKRVPrIECgRNOxtgwE3GvpjU6vUZOm7v03iBHatqYWmSFu1sU=',
                    'updated'           => '2023-02-13 13:21:23',
                ],
            ),
        );
        $this->privateSetUp();
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
     * @return void
     * @throws GuzzleException
     * @throws ApplicationInstallException
     * @throws Exception
     */
    public function testUpdateApplicationPassword(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{"name":"null"}]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":null,"name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_JvxO9FaH6uNM6+wjwsaQEkz+fF6VyF+xS3+lBMzMWvE=:GiwVCx\/v8p+P4nCzJeVBQLVgQZD\/0BHHUN\/LIHNK8WA=:93Vx1D26qlpncGVdKTpQscpUKdQ+P5vj:eM1CUKfoEPs6pP5JMt\/USNGXR3yPcJlxYXpbkRadsC\/d4lpvhRUDs8qQDzNgDgN3oSoRNMfId2JtdqYki0p+CbV8LVnixZEhIlAzWj2AakU0CrvEofrFjQ==","settings":[],"created":"2023-02-13 13:24:27","updated":"2023-02-13 13:24:27","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '[]'),
                [
                    'created'           => '2023-02-13 13:24:27',
                    'encryptedSettings' => '001_JvxO9FaH6uNM6+wjwsaQEkz+fF6VyF+xS3+lBMzMWvE=:GiwVCx/v8p+P4nCzJeVBQLVgQZD/0BHHUN/LIHNK8WA=:93Vx1D26qlpncGVdKTpQscpUKdQ+P5vj:eM1CUKfoEPs6pP5JMt/USNGXR3yPcJlxYXpbkRadsC/d4lpvhRUDs8qQDzNgDgN3oSoRNMfId2JtdqYki0p+CbV8LVnixZEhIlAzWj2AakU0CrvEofrFjQ==',
                    'updated'           => '2023-02-13 13:24:27',
                ],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    '[{"name":"null","encryptedSettings":"001_JvxO9FaH6uNM6+wjwsaQEkz+fF6VyF+xS3+lBMzMWvE=:GiwVCx/v8p+P4nCzJeVBQLVgQZD/0BHHUN/LIHNK8WA=:93Vx1D26qlpncGVdKTpQscpUKdQ+P5vj:eM1CUKfoEPs6pP5JMt/USNGXR3yPcJlxYXpbkRadsC/d4lpvhRUDs8qQDzNgDgN3oSoRNMfId2JtdqYki0p+CbV8LVnixZEhIlAzWj2AakU0CrvEofrFjQ=="}]',
                ),
            ),
        );
        $this->privateSetUp();
        $this->handler->updateApplicationPassword(
            'null',
            'user',
            [
                'fieldKey' => BasicApplicationInterface::PASSWORD,
                'formKey'  => ApplicationInterface::AUTHORIZATION_FORM,
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
     * @throws Exception
     */
    public function testUpdateApplicationPasswordErr(): void
    {
        $this->privateSetUp();
        self::expectException(InvalidArgumentException::class);
        $this->handler->updateApplicationPassword(
            'null',
            'user',
            [
                'fieldKey' => BasicApplicationInterface::PASSWORD,
                'formKey'  => ApplicationInterface::AUTHORIZATION_FORM,
                'username' => 'newUsername',
            ],
        );
    }

    /**
     * @throws Exception
     */
    protected function privateSetUp(): void
    {
        $this->handler = self::getContainer()->get('hbpf.application.handler');
    }

}
