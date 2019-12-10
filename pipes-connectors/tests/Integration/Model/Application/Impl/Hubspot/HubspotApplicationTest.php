<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Hubspot;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubspotCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubspotApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class HubspotApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Hubspot
 */
final class HubspotApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '3cc4771e-deb7-4905-8e6b-d2**********';

    /**
     * @var HubspotApplication
     */
    private $application;

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
     *
     */
    public function testGetSettingsForm(): void
    {
        $this->setApplication();
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains(
                $field->getKey(),
                [
                    'app_id',
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
        $this->assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);
    }

    /**
     * @throws DateTimeException
     */
    public function testIsAuthorizedNoToken(): void
    {
        $this->setApplication();
        $applicationInstall = new ApplicationInstall();
        $this->pf($applicationInstall);
        $this->assertEquals(FALSE, $this->application->isAuthorized($applicationInstall));
    }

    /**
     *
     */
    public function testGetWebhookSubscriptions(): void
    {
        $this->setApplication();
        $webhookSubcription = $this->application->getWebhookSubscriptions();
        $this->assertInstanceOf(WebhookSubscription::class, $webhookSubcription[0]);
        $this->assertInstanceOf(WebhookSubscription::class, $webhookSubcription[1]);
        $this->assertEquals('contact.creation', $webhookSubcription[0]->getParameters()['name']);
        $this->assertEquals('contact.deletion', $webhookSubcription[1]->getParameters()['name']);
    }

    /**
     * @throws DateTimeException
     */
    public function testprocessWebhookSubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', []),
            new ApplicationInstall()
        );
        $this->assertIsNumeric($response);
    }

    /**
     *
     */
    public function testprocessWebhookUnsubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', [])
        );
        $this->assertEquals(TRUE, $response);
    }

    /**
     * @throws DateTimeException
     * @throws CurlException
     * @throws ApplicationInstallException
     */
    public function testGetWebhookSubscribeRequestDto(): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    sprintf('response200.json'),
                    []
                ),
            ]
        );
        $this->setApplication();
        $hubspotCreateContactConnector = new HubspotCreateContactConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $hubspotCreateContactConnector->setApplication($this->application);
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationAbstract::FORM                    => ['app_id' => '123xx'],
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

        /** @var Uri $uri */
        $uri = $response->getUri();
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals('webhooks/v1/123xx/subscriptions', $uri->getPath());
        $this->assertEquals(
            '{"subscriptionDetails":{"subscriptionType":"name2","propertyName":"email","enabled":true}}',
            $response->getBody()
        );
        /** @var Uri $uriUn */
        $uriUn = $responseUn->getUri();
        $this->assertEquals('DELETE', $responseUn->getMethod());
        $this->assertEquals('webhooks/v1/123xx/subscriptions/id123', $uriUn->getPath());

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
