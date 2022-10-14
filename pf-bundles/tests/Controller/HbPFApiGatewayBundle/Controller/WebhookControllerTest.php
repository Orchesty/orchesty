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
     *
     * @throws Exception
     */
    public function testSubscribeWebhookAction(): void
    {
        $this->createApplication();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/WebhookController/subscribeWebhooksRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\WebhookController::unsubscribeWebhooksAction
     *
     * @throws Exception
     */
    public function testUnsubscribeWebhookAction(): void
    {
        $this->createApplication();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/WebhookController/unsubscribeWebhooksRequest.json');
    }

    /**
     * @throws Exception
     */
    private function createApplication(): void
    {
        $application = (new ApplicationInstall())->setKey('null')->setUser('orchesty');
        $this->pfd($application);

        $sdk = new Sdk();
        $sdk->setUrl('php-sdk')->setName('php-sdk');
        $this->pfd($sdk);
    }

}
