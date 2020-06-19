<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
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
        $this->createApplication();
        $this->assertResponse(__DIR__ . '/data/WebhookController/subscribeWebhooksRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\WebhookController::unsubscribeWebhooksAction
     */
    public function testUnsubscribeWebhookAction(): void
    {
        $this->createApplication();
        $this->assertResponse(__DIR__ . '/data/WebhookController/unsubscribeWebhooksRequest.json');
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplication(): ApplicationInstall
    {
        $application = (new ApplicationInstall())->setKey('null')->setUser('user');
        $this->pfd($application);

        $sdk = new Sdk();
        $sdk->setKey('php-sdk')->setValue('php-sdk');
        $this->pfd($sdk);

        return $application;
    }

}
