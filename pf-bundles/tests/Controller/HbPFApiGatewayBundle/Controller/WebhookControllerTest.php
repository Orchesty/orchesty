<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\WebhookController;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class WebhookControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(WebhookController::class)]
final class WebhookControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testSubscribeWebhookAction(): void
    {
        $this->createApplication();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/WebhookController/subscribeWebhooksRequest.json');
    }

    /**
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
