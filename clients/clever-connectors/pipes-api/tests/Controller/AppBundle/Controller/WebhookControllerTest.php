<?php declare(strict_types=1);

namespace Tests\Controller\AppBundle\Controller;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Document\Webhook;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Tests\ControllerTestCaseAbstract;

/**
 * Class WebhookControllerTest
 *
 * @package Tests\Controller\AppBundle\Controller
 */
final class WebhookControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testWebhookAction(): void
    {
        $handler = $this->getMockBuilder(StartingPointHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('runWithRequest')->willReturn('');
        $this->client->getContainer()->set('hbpf.handler.starting_point', $handler);

        $resp = (new ResponseDto(200, '', '', []));
        $curl = $this->getMockBuilder(CurlManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curl->method('send')->willReturn($resp);
        $this->client->getContainer()->set('cc.transport.curl.manager', $curl);

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setSystem('null.user')
            ->setToken('token')
            ->setUser('userId');
        $this->persistAndFlush($systemInstall);

        $web = (new Webhook())
            ->setSystemKey('null.user')
            ->setTopologyName('topName')
            ->setNodeName('nodeName');
        $this->persistAndFlush($web);

        $this->client->request('POST', '/webhook/userId/token/nodeName/topName', [], [], []);

        $res = $this->client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
    }

    /**
     *
     */
    public function testWebhookActionNonAuthorized(): void
    {
        $handler = $this->getMockBuilder(StartingPointHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('runWithRequest')->willReturn('');
        $this->client->getContainer()->set('hbpf.handler.starting_point', $handler);

        $resp = (new ResponseDto(403, '', '', []));
        $curl = $this->getMockBuilder(CurlManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curl->method('send')->willReturn($resp);
        $this->client->getContainer()->set('hbpf.transport.curl_manager', $curl);

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setSystem('null.user')
            ->setToken('token')
            ->setUser('userId');
        $this->persistAndFlush($systemInstall);

        $web = (new Webhook())
            ->setSystemKey('null.user')
            ->setTopologyName('topName')
            ->setNodeName('nodeName');
        $this->persistAndFlush($web);

        $this->client->request('POST', '/webhook/userId/token/nodeName/topName', [], [], []);
        $res = $this->client->getResponse();
        self::assertEquals(403, $res->getStatusCode());
    }

    /**
     *
     */
    public function testWebhookActionNonExistingWebhook(): void
    {
        $handler = $this->getMockBuilder(StartingPointHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('runWithRequest')->willReturn('');
        $this->client->getContainer()->set('hbpf.handler.starting_point', $handler);

        $resp = (new ResponseDto(200, '', '', []));
        $curl = $this->getMockBuilder(CurlManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curl->method('send')->willReturn($resp);
        $this->client->getContainer()->set('hbpf.transport.curl_manager', $curl);

        $web = new Webhook();
        $web->setTopologyName('topNameFlase')->setNodeName('nodeNameFalse');
        $this->persistAndFlush($web);

        $this->client->request('POST', '/webhook/userId/token/nodeName/topName', [], [], []);
        $res = $this->client->getResponse();
        self::assertEquals(404, $res->getStatusCode());
    }

}