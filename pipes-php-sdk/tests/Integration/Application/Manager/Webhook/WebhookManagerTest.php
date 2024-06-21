<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Manager\Webhook;

use Closure;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Application\Repository\WebhookRepository;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class WebhookManagerTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Manager\Webhook
 */
#[CoversClass(WebhookManager::class)]
#[CoversClass(WebhookSubscription::class)]
#[CoversClass(Webhook::class)]
final class WebhookManagerTest extends KernelTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @var WebhookApplication
     */
    private WebhookApplication $application;

    /**
     * @var WebhookRepository $repository
     */
    private WebhookRepository $repository;

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws GuzzleException
     * @throws CurlException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testSubscribeAndUnsubscribe(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["webhook"],"users":["User"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode((new ApplicationInstall())->setUser('User')->setKey('webhook')->toArray()),
                ),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook',
                Json::decode(
                    '[{"id":null,"user":"User","application":"webhook","created":"2023-02-13 11:18:55","name":"name","webhookId":"id","node":"name","token":"a344c694874a1ebb8fb0881714c2c424b3e5fbd895cded820c","topology":"topology"}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '[]'),
                ['created' => '2023-02-13 11:18:55', 'token' => 'a344c694874a1ebb8fb0881714c2c424b3e5fbd895cded820c'],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode(
                        (new Webhook(
                            [
                                'application' => 'webhook',
                                'node'        => 'node',
                                'token'       => 'a344c694874a1ebb8fb0881714c2c424b3e5fbd895cded820c',
                                'topology'    => 'topology',
                                'user'        => 'User',
                                'webhook'     => 'webhook',
                                'webhookId'   => 'id',
                            ],
                        ))->toArray(),
                    ),
                ),
                ['created' => '2023-02-13 11:18:55', 'token' => 'a344c694874a1ebb8fb0881714c2c424b3e5fbd895cded820c'],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook?filter={"applications":["webhook"],"user_uds":["User"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[]'),
            ),
        );
        $this->privateSetUp();
        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->subscribeWebhooks($this->application, 'User');

        /** @var Webhook[] $webhooks */
        $webhooks = $this->repository->findMany();
        self::assertCount(1, $webhooks);
        self::assertEquals('User', $webhooks[0]->getUser());
        self::assertEquals(50, strlen($webhooks[0]->getToken() ?? ''));
        self::assertEquals('node', $webhooks[0]->getNode());
        self::assertEquals('topology', $webhooks[0]->getTopology());
        self::assertEquals('webhook', $webhooks[0]->getApplication());
        self::assertEquals('id', $webhooks[0]->getWebhookId());
        self::assertEquals(FALSE, $webhooks[0]->isUnsubscribeFailed());

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"success":true}', []))
            ->unsubscribeWebhooks($this->application, 'User');

        self::assertCount(0, $this->repository->findMany());
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testSubscribeAndUnsubscribeFailed(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["webhook"],"users":["User"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{}]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook',
                Json::decode(
                    '[{"created":"2023-02-13 11:55:26","id":null,"name":"name","node":"name","token":"cccb4b3a5f16783305e7ac945b03708832450ef6093217e9a3","topology":"topology","webhookId":"id","application":"webhook","user":"User"}]',
                ),
                CurlManager::METHOD_POST,
                new Response(
                    200,
                    [],
                    Json::encode((new ApplicationInstall())->setUser('User')->setKey('webhook')->toArray()),
                ),
                ['created' => '2023-02-13 11:55:26', 'token' => '7d2fe1873b77049267371062a784c4923b65a6e4a3cf549294'],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook?filter={"applications":["webhook"],"user_uds":["User"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{}]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["webhook"],"users":["User"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{}]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    '[{"user":"User","node":"node","topology":"topology","application":"webhook","webhookId":"id","unsubscribeFailed":true}]',
                ),
            ),
        );
        $this->privateSetUp();
        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->subscribeWebhooks($this->application, 'User');

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"success":false}', []))
            ->unsubscribeWebhooks($this->application, 'User');

        /** @var Webhook[] $webhooks */
        $webhooks = $this->repository->findMany();
        self::assertCount(1, $webhooks);
        self::assertEquals('User', $webhooks[0]->getUser());
        self::assertEquals('node', $webhooks[0]->getNode());
        self::assertEquals('topology', $webhooks[0]->getTopology());
        self::assertEquals('webhook', $webhooks[0]->getApplication());
        self::assertEquals('id', $webhooks[0]->getWebhookId());
        self::assertEquals(TRUE, $webhooks[0]->isUnsubscribeFailed());
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testSubscribeAndUnsubscribeNoApplication(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["webhook"],"users":["User"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[]'),
            ),
        );
        $this->privateSetUp();
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->subscribeWebhooks($this->application, 'User');
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetWebhooks(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook?filter={"applications":["webhook"],"user_uds":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{"name":"name","default":true,"enabled":false,"topology":"1"}]'),
            ),
        );
        $this->privateSetUp();

        $result = $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->getWebhooks($this->application, 'user');

        self::assertEquals(
            [
                'default'  => TRUE,
                'enabled'  => TRUE,
                'name'     => 'name',
                'topology' => '1',
            ],
            $result[0],
        );
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testSubscribeWebhooks(): void
    {
        $this->privateSetUp();
        $params = (new WebhookSubscription('name', 'node', 'topo', []))->getParameters();

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->subscribeWebhooks($this->application, 'user', ['name' => 'testName']);

        self::assertEquals([], $params);
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testUnsubscribeWebhooks(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/Webhook?filter={"applications":["webhook"],"user_uds":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[]'),
            ),
        );
        $this->privateSetUp();
        $this
            ->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->unsubscribeWebhooks($this->application, 'user', ['topology' => 'testTopo']);

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    protected function privateSetUp(): void
    {
        $this->application = self::getContainer()->get('hbpf.application.webhook');

        $this->repository = self::getContainer()->get('hbpf.webhook.repository');
    }

    /**
     * @param Closure $closure
     *
     * @return WebhookManager
     * @throws Exception
     */
    private function getService(Closure $closure): WebhookManager
    {
        $manager = self::createMock(CurlManagerInterface::class);
        $manager->expects(self::any())->method('send')->willReturnCallback($closure);

        /** @var ApplicationInstallRepository $applicationInstallRepository */
        $applicationInstallRepository = self::getContainer()->get('hbpf.application_install.repository');

        return new WebhookManager($applicationInstallRepository, $this->repository, $manager, 'https://example.com');
    }

}
