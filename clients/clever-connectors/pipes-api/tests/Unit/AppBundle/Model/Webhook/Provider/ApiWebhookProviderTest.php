<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.10.17
 * Time: 13:22
 */

namespace Tests\Unit\AppBundle\Model\Webhook\Provider;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Model\Webhook\Provider\ApiWebhookProvider;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;
use Tests\KernelTestCaseAbstract;

/**
 * Class ApiWebhookProviderTest
 *
 * @package Tests\Unit\AppBundle\Model\Webhook\Provider
 */
final class ApiWebhookProviderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testSubscribe(): void
    {
        $provider = $this->mockProvider();
        $oauth2   = $this->container->get('hbpf.providers.oauth2_provider');
        $system   = new NullSystem($oauth2);
        $provider->subscribe($system, '123', '456');
    }

    /**
     *
     */
    public function testUnSubscribe(): void
    {
        $provider = $this->mockProvider();
        $oauth2   = $this->container->get('hbpf.providers.oauth2_provider');
        $system   = new NullSystem($oauth2);
        $provider->unsubscribe($system, '123');
    }

    /**
     *
     */
    public function testUpdate(): void
    {
        $provider = $this->mockProvider();
        $oauth2   = $this->container->get('hbpf.providers.oauth2_provider');
        $system   = new NullSystem($oauth2);
        $provider->update($system, '123', '456');
    }

    /**
     * ------------------------------------- HELPERS ---------------------------------
     */

    /**
     * @return ApiWebhookProvider
     */
    private function mockProvider(): ApiWebhookProvider
    {
        $webhook = $this->createMock(Webhook::class);
        $webhook->method('getWebhookId')->willReturn('123456');

        $webhookRepo = $this->createMock(WebhookRepository::class);
        $webhookRepo->method('isWebhookRegistred')->willReturn(FALSE);
        $webhookRepo->method('findBy')->willReturn([$webhook]);

        $systemInstall = (new SystemInstall())->setSystem('systems.null.user')->setUser('1')->setToken('2');

        $systemRepo = $this->createMock(SystemInstallRepository::class);
        $systemRepo->method('findOneBy')->willReturn($systemInstall);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('persist')->willReturn(NULL);
        $dm->method('flush')->willReturn(NULL);
        $dm->method('remove')->willReturn(NULL);
        $dm->expects($this->at(0))->method('getRepository')->willReturn($webhookRepo);
        $dm->expects($this->at(1))->method('getRepository')->willReturn($systemRepo);

        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturn(new ResponseDto(200, '', 'body', []));

        return new ApiWebhookProvider($dm, $curl, 'http://localhost/');
    }

}