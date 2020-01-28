<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubspotCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubspotApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
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
     * @var HubspotApplication
     */
    private HubspotApplication $application;

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
    public function testName(): void
    {
        $this->setApplication();
        self::assertEquals('Hubspot', $this->application->getName());
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Hubspot v1',
            $this->application->getDescription()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $this->setApplication();
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertContains(
                $field->getKey(),
                [
                    HubspotApplication::USER_ID,
                    HubspotApplication::HAPI_KEY,
                    HubspotApplication::APP_ID,
                    OAuth2ApplicationInterface::CLIENT_ID,
                    OAuth2ApplicationInterface::CLIENT_SECRET,
                ]
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getKey(),
            'user',
            'token',
            self::CLIENT_ID
        );
        $this->pf($applicationInstall);
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
        $this->pf($applicationInstall);
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
            new ApplicationInstall()
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
            new ResponseDto(204, '', '{"id":"id88"}', [])
        );
        self::assertEquals(TRUE, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetWebhookSubscribeRequestDto(): void
    {
        $this->setApplication();
        $hubspotCreateContactConnector = new HubspotCreateContactConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $hubspotCreateContactConnector->setApplication($this->application);
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationAbstract::FORM                    => [
                    HubspotApplication::APP_ID   => '123xx',
                    HubspotApplication::HAPI_KEY => '21a0d413-e204-4138-9ede-************',
                    HubspotApplication::USER_ID  => '89*****',
                ],
                ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => 'token123']],
            ]
        );
        $this->pf(DataProvider::getOauth2AppInstall($this->application->getKey()));
        $webhookSubscription = new WebhookSubscription(
            'name',
            'node',
            'topology',
            ['name' => 'name2']
        );
        $response            = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $webhookSubscription,
            ''
        );
        $responseUn          = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            'id123'
        );

        self::assertEquals('POST', $response->getMethod());
        self::assertEquals('DELETE', $responseUn->getMethod());
        self::assertEquals(
            'https://api.hubapi.com/webhooks/v1/123xx/subscriptions?hapikey=21a0d413-e204-4138-9ede-************&userId=89*****',
            $response->getUriString()
        );
        self::assertEquals(
            '{"subscriptionDetails":{"subscriptionType":"name2","propertyName":"email"},"enabled":false}',
            $response->getBody()
        );
        self::assertEquals(
            'https://api.hubapi.com/webhooks/v1/123xx/subscriptions/id123?hapikey=21a0d413-e204-4138-9ede-************&userId=89*****',
            $responseUn->getUriString()
        );
    }

    /**
     *
     */
    private function setApplication(): void
    {
        $this->mockRedirect(HubspotApplication::HUBSPOT_URL, self::CLIENT_ID, 'contacts');
        $this->application = self::$container->get('hbpf.application.hubspot');
    }

}
