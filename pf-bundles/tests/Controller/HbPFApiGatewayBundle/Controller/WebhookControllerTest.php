<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class WebhookControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\WebhookController
 */
final class WebhookControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\WebhookController::subscribeWebhooksAction
     */
    public function testSubscribeWebhookAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/WebhookController/subscribeWebhooksRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\WebhookController::unsubscribeWebhooksAction
     */
    public function testUnsubscribeWebhookAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/WebhookController/unsubscribeWebhooksRequest.json');
    }

}
