<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shopify;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ShopifyApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shopify
 */
final class ShopifyApplicationTest extends DatabaseTestCaseAbstract
{

    private const API_KEY  = 'd4500771446672c5390187**********';
    private const PASSWORD = 'b0ef2faa171c1d45460fa8**********';

    /**
     * @var ShopifyApplication
     */
    private $application;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->application = self::$container->get('hbpf.application.shopify');
    }

    /**
     * @throws Exception
     */
    public function testAuthorization(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->application->getKey()
        );

        $applicationInstall->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        ShopifyApplication::SHOP           => 'TestHanaHana',
                        BasicApplicationInterface::USER     => self::API_KEY,
                        BasicApplicationInterface::PASSWORD => self::PASSWORD,
                    ],
            ]
        );

        $this->pf($applicationInstall);

        $dto = $this->application->getRequestDto(
            $applicationInstall,
            'POST',
            'customers.json',
            '{"customer": 
            {"first_name": "Steve", "last_name": "Lastnameson", "email": "steve.lastnameson@example.com", "phone": "+15142546011", "verified_email": true, "addresses": 
            [{"address1": "123 Oak St","city": "Ottawa","province": "ON","phone": "555-1212","zip": "123 ABC","last_name": "Lastnameson","first_name": "Mother","country": "CA"}],
            "password": "newpass","password_confirmation": "newpass","send_email_welcome": false}}'
        );

        $this->assertEquals('POST', $dto->getMethod());
        $this->assertEquals(
            'https://d4500771446672c5390187**********:b0ef2faa171c1d45460fa8**********@testhanahana.myshopify.com/admin/api/2019-10/customers.json',
            $dto->getUriString()
        );
        $this->assertEquals(
            '{"customer": 
            {"first_name": "Steve", "last_name": "Lastnameson", "email": "steve.lastnameson@example.com", "phone": "+15142546011", "verified_email": true, "addresses": 
            [{"address1": "123 Oak St","city": "Ottawa","province": "ON","phone": "555-1212","zip": "123 ABC","last_name": "Lastnameson","first_name": "Mother","country": "CA"}],
            "password": "newpass","password_confirmation": "newpass","send_email_welcome": false}}',
            $dto->getBody()
        );
    }

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(
            ApplicationTypeEnum::WEBHOOK,
            $this->application->getApplicationType()
        );
    }

    /**
     *
     */
    public function testName(): void
    {
        self::assertEquals(
            'Shopify',
            $this->application->getName()
        );
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Shopify v1',
            $this->application->getDescription()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains($field->getKey(), ['user', 'password', 'shop']);
        }

    }

    /**
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->application->getKey()
        );

        $applicationInstall->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        ShopifyApplication::SHOP            => 'TestHanaHana',
                        BasicApplicationInterface::USER     => self::API_KEY,
                        BasicApplicationInterface::PASSWORD => self::PASSWORD,
                    ],
            ]
        );

        $this->pf($applicationInstall);

        $subscription = new WebhookSubscription('test', 'node', 'xxx', ['name' => 'customers/create']);

        $requestSub = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $subscription,
            'https://www.xx.cz'
        );

        $requestUn = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            '726899654794'
        );

        self::assertEquals(
            'https://d4500771446672c5390187**********:b0ef2faa171c1d45460fa8**********@testhanahana.myshopify.com/admin/api/2019-10/webhooks.json',
            $requestSub->getUriString()
        );

        self::assertEquals(
            '{"webhook":{"address":"https:\/\/www.xx.cz","topic":"customers\/create","format":"json"}}',
            $requestSub->getBody()
        );

        self::assertEquals(
            'https://d4500771446672c5390187**********:b0ef2faa171c1d45460fa8**********@testhanahana.myshopify.com/admin/api/2019-10/webhooks/726899654794.json',
            $requestUn->getUriString()
        );

    }

    /**
     *
     */
    public function testGetWebhookSubscriptions(): void
    {
        $webhookSubcription = $this->application->getWebhookSubscriptions();
        $this->assertInstanceOf(WebhookSubscription::class, $webhookSubcription[0]);
        $this->assertEquals('customers/create', $webhookSubcription[0]->getParameters()['name']);
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, '', '{"webhook": {"id": 726666666866}}', []),
            new ApplicationInstall()
        );
        $this->assertEquals('726666666866', $response);
    }

    /**
     *
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(200, '', '', [])
        );
        $this->assertEquals(200, $response);
    }

}
