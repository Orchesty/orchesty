<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Manager\Webhook;

use Closure;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Repository\WebhookRepository;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class WebhookManagerTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Manager\Webhook
 */
final class WebhookManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var WebhookApplication
     */
    private WebhookApplication $application;

    /**
     * @var ObjectRepository<Webhook>&WebhookRepository
     */
    private $repository;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::subscribeWebhooks
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::unsubscribeWebhooks
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription::getTopology
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription::getNode
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription::getName
     *
     * @throws Exception
     */
    public function testSubscribeAndUnsubscribe(): void
    {
        $this->dm->persist((new ApplicationInstall())->setUser('User')->setKey('webhook'));
        $this->dm->flush();

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->subscribeWebhooks($this->application, 'User');
        $this->dm->clear();

        /** @var Webhook[] $webhooks */
        $webhooks = $this->repository->findAll();
        self::assertCount(1, $webhooks);
        self::assertEquals('User', $webhooks[0]->getUser());
        self::assertEquals(50, strlen($webhooks[0]->getToken()));
        self::assertEquals('node', $webhooks[0]->getNode());
        self::assertEquals('topology', $webhooks[0]->getTopology());
        self::assertEquals('webhook', $webhooks[0]->getApplication());
        self::assertEquals('id', $webhooks[0]->getWebhookId());
        self::assertEquals(FALSE, $webhooks[0]->isUnsubscribeFailed());

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"success":true}', []))
            ->unsubscribeWebhooks($this->application, 'User');

        self::assertCount(0, $this->repository->findAll());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::subscribeWebhooks
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::unsubscribeWebhooks
     *
     * @throws Exception
     */
    public function testSubscribeAndUnsubscribeFailed(): void
    {
        $this->dm->persist((new ApplicationInstall())->setUser('User')->setKey('webhook'));
        $this->dm->flush();

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->subscribeWebhooks($this->application, 'User');
        $this->dm->clear();

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"success":false}', []))
            ->unsubscribeWebhooks($this->application, 'User');

        /** @var Webhook[] $webhooks */
        $webhooks = $this->repository->findAll();
        self::assertCount(1, $webhooks);
        self::assertEquals('User', $webhooks[0]->getUser());
        self::assertEquals('node', $webhooks[0]->getNode());
        self::assertEquals('topology', $webhooks[0]->getTopology());
        self::assertEquals('webhook', $webhooks[0]->getApplication());
        self::assertEquals('id', $webhooks[0]->getWebhookId());
        self::assertEquals(TRUE, $webhooks[0]->isUnsubscribeFailed());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::subscribeWebhooks
     *
     * @throws Exception
     */
    public function testSubscribeAndUnsubscribeNoApplication(): void
    {
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->subscribeWebhooks($this->application, 'User');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::getWebhooks
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription::getTopology
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription::getName
     *
     * @throws Exception
     */
    public function testGetWebhooks(): void
    {
        $webhook = (new Webhook())
            ->setUser('user')
            ->setApplication('webhook')
            ->setName('name')
            ->setTopology('1');
        $this->pfd($webhook);

        $result = $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->getWebhooks($this->application, 'user');

        self::assertEquals(
            [
                'name'     => 'name',
                'default'  => TRUE,
                'enabled'  => TRUE,
                'topology' => '1',
            ],
            $result[0],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::subscribeWebhooks
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription::getParameters
     *
     * @throws Exception
     */
    public function testSubscribeWebhooks(): void
    {
        $params = (new WebhookSubscription('name', 'node', 'topo', []))->getParameters();
        $this->dm->persist((new ApplicationInstall())->setUser('user')->setKey('webhook'));
        $this->dm->flush();

        $this->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->subscribeWebhooks($this->application, 'user', ['name' => 'testName']);

        self::assertEquals([], $params);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::unsubscribeWebhooks
     *
     * @throws Exception
     */
    public function testUnsubscribeWebhooks(): void
    {
        $this->dm->persist((new ApplicationInstall())->setUser('user')->setKey('webhook'));
        $this->dm->flush();

        $webhook = (new Webhook())
            ->setUser('user')
            ->setApplication('webhook')
            ->setName('name')
            ->setTopology('1');
        $this->pfd($webhook);

        $this
            ->getService(static fn(): ResponseDto => new ResponseDto(200, 'OK', '{"id":"id"}', []))
            ->unsubscribeWebhooks($this->application, 'user', ['topology' => 'testTopo']);

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.webhook');
        $this->repository  = $this->dm->getRepository(Webhook::class);
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

        return new WebhookManager($this->dm, $manager, 'https://example.com');
    }

}
