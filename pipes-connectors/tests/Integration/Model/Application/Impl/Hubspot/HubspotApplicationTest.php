<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubSpotApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class HubspotApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot
 */
final class HubspotApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '3cc4771e-deb7-4905-8e6b-d2**********';

    /**
     * @var HubSpotApplication
     */
    private HubSpotApplication $application;

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertEquals(ApplicationTypeEnum::WEBHOOK, $this->application->getApplicationType());
    }

    /**
     *
     */
    public function testPublicName(): void
    {
        $this->setApplication();
        self::assertEquals('HubSpot Application', $this->application->getPublicName());
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals(
            'HubSpot offers a full stack of software for marketing, sales, and customer service, with a completely free CRM at its core. They’re powerful alone — but even better when used together.',
            $this->application->getDescription(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $this->setApplication();
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form){
            foreach ($form->getFields() as $field) {
                self::assertContainsEquals(
                    $field->getKey(),
                    [
                        HubSpotApplication::APP_ID,
                        OAuth2ApplicationInterface::CLIENT_ID,
                        OAuth2ApplicationInterface::CLIENT_SECRET,
                    ],
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'token',
            self::CLIENT_ID,
        );
        $this->pfd($applicationInstall);
        self::assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorizedNoToken(): void
    {
        $this->setApplication();
        $applicationInstall = new ApplicationInstall();
        $this->pfd($applicationInstall);
        self::assertEquals(FALSE, $this->application->isAuthorized($applicationInstall));
    }

    /**
     *
     */
    public function testGetWebhookSubscriptions(): void
    {
        $this->setApplication();
        $webhookSubcription = $this->application->getWebhookSubscriptions();
        self::assertEquals('contact.creation', $webhookSubcription[0]->getParameters()['name']);
        self::assertEquals('contact.deletion', $webhookSubcription[1]->getParameters()['name']);
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', []),
            new ApplicationInstall(),
        );
        self::assertEquals('id88', $response);
    }

    /**
     *
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(204, '', '{"id":"id88"}', []),
        );
        self::assertEquals(TRUE, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetWebhookSubscribeRequestDto(): void
    {
        $this->setApplication();
        $hubspotCreateContactConnector = new HubSpotCreateContactConnector();
        $hubspotCreateContactConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setDb($this->dm)
            ->setApplication($this->application);

        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    HubSpotApplication::APP_ID => '123xx',
                    ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => 'token123'],
                ],
            ],
        );
        $this->pfd(DataProvider::getOauth2AppInstall($this->application->getName()));
        $webhookSubscription = new WebhookSubscription(
            'name',
            'node',
            'topology',
            ['name' => 'name2'],
        );
        $response            = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $webhookSubscription,
            '',
        );
        $responseUn          = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            new Webhook('id123'),
        );

        self::assertEquals('POST', $response->getMethod());
        self::assertEquals('DELETE', $responseUn->getMethod());
        self::assertEquals(
            'https://api.hubapi.com/webhooks/v1/123xx/subscriptions',
            $response->getUriString(),
        );
        self::assertEquals(
            '{"webhookUrl":"","subscriptionDetails":{"subscriptionType":"name2","propertyName":"email"},"enabled":false}',
            $response->getBody(),
        );
        self::assertEquals(
            'https://api.hubapi.com/webhooks/v1/123xx/subscriptions/id123',
            $responseUn->getUriString(),
        );
    }

    /**
     *
     */
    private function setApplication(): void
    {
        $this->mockRedirect(HubSpotApplication::HUBSPOT_URL, self::CLIENT_ID, 'contacts');
        $this->application = self::getContainer()->get('hbpf.application.hub-spot');
    }

}
