<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shipstation;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\ShipstationApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ShipstationApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shipstation
 */
final class ShipstationApplicationTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = '79620d3760d**********18f8a35dec8';
    public const API_SECRET = '9cabe470**********751904f45f80e2';

    public const token = 'ODkxOWJiMjEzYWFiNDdiNDhmN2JiMDdmMWNlMWUyNWM6OTk**********jE1NDQ5OWEzODIyMWQyMjM3NTQyNGI=';

    /**
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $application        = self::$container->get('hbpf.application.shipstation');
        $applicationInstall = DataProvider::getBasicAppInstall(
            $application->getKey(),
            self::API_KEY,
            self::API_SECRET
        );

        $subscription = new WebhookSubscription('test', 'node', 'xxx', ['name' => 0]);

        $requestSub = $application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $subscription,
            sprintf(
                '%s/webhook/topologies/%s/nodes/%s/token/%s',
                rtrim('www.xx.cz', '/'),
                $subscription->getTopology(),
                $subscription->getNode(),
                bin2hex(random_bytes(25))
            )
        );

        $requestUn = $application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            '358'
        );

        self::assertEquals(
            $requestSub->getUriString(),
            'https://ssapi.shipstation.com/webhooks/subscribe'
        );

        self::assertEquals(
            $requestUn->getUriString(),
            'https://ssapi.shipstation.com/webhooks/358'
        );

    }

    /**
     *
     */
    public function testName(): void
    {
        $shipstation = self::$container->get('hbpf.application.shipstation');
        self::assertEquals(
            'Shipstation',
            $shipstation->getName()
        );
    }

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        $shipstation = self::$container->get('hbpf.application.shipstation');
        self::assertEquals(
            ApplicationTypeEnum::WEBHOOK,
            $shipstation->getApplicationType()
        );
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        $shipstation = self::$container->get('hbpf.application.shipstation');
        self::assertEquals(
            'Shipstation v1',
            $shipstation->getDescription()
        );
    }

    /**
     *
     */
    public function testGetWebhookSubscriptions(): void
    {
        $shipstation        = self::$container->get('hbpf.application.shipstation');
        $webhookSubcription = $shipstation->getWebhookSubscriptions();
        $this->assertInstanceOf(WebhookSubscription::class, $webhookSubcription[0]);
        $this->assertEquals(ShipstationApplication::ORDER_NOTIFY, $webhookSubcription[0]->getParameters()['name']);
    }

    /**
     *
     */
    public function testGetSettingsForm(): void
    {
        $shipstation = self::$container->get('hbpf.application.shipstation');

        $fields = $shipstation->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains($field->getKey(), ['user', 'password']);
        }

    }

    /**
     * @throws DateTimeException
     */
    public function testprocessWebhookSubscribeResponse(): void
    {
        $shipstation = self::$container->get('hbpf.application.shipstation');
        $response    = $shipstation->processWebhookSubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', []),
            new ApplicationInstall()
        );
        $this->assertEquals('id88', $response);
    }

    /**
     *
     */
    public function testprocessWebhookUnsubscribeResponse(): void
    {
        $shipstation = self::$container->get('hbpf.application.shipstation');
        $response    = $shipstation->processWebhookUnsubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', [])
        );
        $this->assertEquals(200, $response);
    }

}
