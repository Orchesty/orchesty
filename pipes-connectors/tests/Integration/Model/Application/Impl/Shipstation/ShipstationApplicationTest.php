<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shipstation;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\ShipstationApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class ShipstationApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shipstation
 */
final class ShipstationApplicationTest extends KernelTestCaseAbstract
{

    public const string API_KEY    = '79620d3760d**********18f8a35dec8';
    public const string API_SECRET = '9cabe470**********751904f45f80e2';

    public const string TOKEN = 'ODkxOWJiMjEzYWFiNDdiNDhmN2JiMDdmMWNlMWUyNWM6OTk**********jE1NDQ5OWEzODIyMWQyMjM3NTQyNGI=';

    /**
     * @var ShipstationApplication
     */
    private ShipstationApplication $application;

    /**
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->application->getName(),
            self::API_KEY,
            self::API_SECRET,
        );

        $subscription = new WebhookSubscription('test', 'node', 'xxx', ['name' => 0]);

        $requestSub = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $subscription,
            sprintf(
                '%s/webhook/topologies/%s/nodes/%s/token/%s',
                rtrim('www.xx.cz', '/'),
                $subscription->getTopology(),
                $subscription->getNode(),
                bin2hex(random_bytes(25)),
            ),
        );

        $requestUn = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            (new Webhook())->setWebhookId('358'),
        );

        self::assertSame('https://ssapi.shipstation.com/webhooks/subscribe', $requestSub->getUriString());
        self::assertSame('https://ssapi.shipstation.com/webhooks/358', $requestUn->getUriString());
    }

    /**
     *
     */
    public function testPublicName(): void
    {
        self::assertSame('Shipstation', $this->application->getPublicName());
    }

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        self::assertSame(ApplicationTypeEnum::WEBHOOK->value, $this->application->getApplicationType());
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        self::assertSame('Shipstation v1', $this->application->getDescription());
    }

    /**
     *
     */
    public function testGetWebhookSubscriptions(): void
    {
        $webhookSubcription = $this->application->getWebhookSubscriptions();
        self::assertEquals(ShipstationApplication::ORDER_NOTIFY, $webhookSubcription[0]->getParameters()['name']);
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains($field->getKey(), ['user', 'password']);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', []),
            new ApplicationInstall(),
        );
        self::assertSame('id88', $response);
    }

    /**
     *
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', []),
        );
        self::assertEquals(200, $response);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.shipstation');
    }

}
