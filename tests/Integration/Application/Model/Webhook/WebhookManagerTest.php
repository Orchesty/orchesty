<?php declare(strict_types=1);

namespace Tests\Integration\Application\Model\Webhook;

use Closure;
use Doctrine\Common\Persistence\ObjectRepository;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Application\Document\Webhook;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class WebhookManagerTest
 *
 * @package Tests\Integration\Application\Model\Webhook
 */
final class WebhookManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var WebhookApplication
     */
    private $application;

    /**
     * @var ObjectRepository
     */
    private $repository;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::$container->get('hbpf.application.webhook');
        $this->repository  = $this->dm->getRepository(Webhook::class);
    }

    /**
     * @throws Exception
     */
    public function testSubscribeAndUnsubscribe(): void
    {
        $this->dm->persist((new ApplicationInstall())->setUser('User')->setKey('webhook'));
        $this->dm->flush();

        $this->getService(function (): ResponseDto {
            return new ResponseDto(200, 'OK', '{"id":"id"}', []);
        })->subscribeWebhooks($this->application, 'User');
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

        $this->getService(function (): ResponseDto {
            return new ResponseDto(200, 'OK', '{"success":true}', []);
        })->unsubscribeWebhooks($this->application, 'User');

        self::assertCount(0, $this->repository->findAll());
    }

    /**
     * @throws Exception
     */
    public function testSubscribeAndUnsubscribeFailed(): void
    {
        $this->dm->persist((new ApplicationInstall())->setUser('User')->setKey('webhook'));
        $this->dm->flush();

        $this->getService(function (): ResponseDto {
            return new ResponseDto(200, 'OK', '{"id":"id"}', []);
        })->subscribeWebhooks($this->application, 'User');
        $this->dm->clear();

        $this->getService(function (): ResponseDto {
            return new ResponseDto(200, 'OK', '{"success":false}', []);
        })->unsubscribeWebhooks($this->application, 'User');

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
     * @throws Exception
     */
    public function testSubscribeAndUnsubscribeNoApplication(): void
    {
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);

        $this->getService(function (): ResponseDto {
            return new ResponseDto(200, 'OK', '{"id":"id"}', []);
        })->subscribeWebhooks($this->application, 'User');
    }

    /**
     * @param Closure $closure
     *
     * @return WebhookManager
     * @throws Exception
     */
    private function getService(Closure $closure): WebhookManager
    {
        /** @var CurlManagerInterface|MockObject $manager */
        $manager = self::createMock(CurlManagerInterface::class);
        $manager->expects(self::any())->method('send')->willReturnCallback($closure);

        return new WebhookManager($this->dm, $manager, 'https://example.com');
    }

}
