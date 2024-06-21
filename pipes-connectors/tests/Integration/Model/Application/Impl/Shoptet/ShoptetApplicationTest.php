<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class ShoptetApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet
 */
#[CoversClass(ShoptetApplication::class)]
final class ShoptetApplicationTest extends KernelTestCaseAbstract
{

    use PrivateTrait;
    use CustomAssertTrait;

    private const CLIENT_ID = '123****';

    /**
     * @var ShoptetApplication
     */
    private ShoptetApplication $application;

    /**
     * @throws Exception
     */
    public function testConstructor(): void
    {
        /** @var OAuth2Provider $provider */
        $provider = self::getContainer()->get('hbpf.providers.oauth2_provider');
        /** @var CurlManager $sender */
        $sender = self::getContainer()->get('hbpf.transport.curl_manager');
        new ShoptetApplication($provider, $sender, 'localhost');

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertEquals(ApplicationTypeEnum::WEBHOOK->value, $this->application->getApplicationType());
    }

    /**
     * @throws Exception
     */
    public function testGetKey(): void
    {
        $this->setApplication();
        self::assertEquals('shoptet', $this->application->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetPublicName(): void
    {
        $this->setApplication();
        self::assertEquals('Shoptet', $this->application->getPublicName());
    }

    /**
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals('Shoptet', $this->application->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $this->setApplication();
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContainsEquals(
                    $field->getKey(),
                    [
                        OAuth2ApplicationAbstract::CLIENT_ID,
                        OAuth2ApplicationAbstract::CLIENT_SECRET,
                        'eshopId',
                        'oauth_url',
                        'api_token_url',
                    ],
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $this->mockSender('{ "access_token": "___access token___", "expires_in": 3600}');

        $this->application = self::getContainer()->get('hbpf.application.shoptet');
        $dto               = $this->application->getRequestDto(
            new ProcessDto(),
            (new ApplicationInstall())
                ->setSettings(
                    [
                        ApplicationInterface::AUTHORIZATION_FORM => [
                            'api_token_url'             => 'https://12345.myshoptet.com/action/ApiOAuthServer/token',
                            ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => '___access_token___'],
                        ],
                    ],
                ),
            'POST',
            'https://example.com',
            '"{"data":"data"}"',
        );

        self::assertEquals('___access token___', $dto->getHeaders()['Shoptet-Access-Token']);
    }

    /**
     * @throws Exception
     */
    public function testGetAuthUrl(): void
    {
        $this->setApplication();
        self::assertEquals('', $this->application->getAuthUrl());
    }

    /**
     * @throws Exception
     */
    public function testGetTokenUrl(): void
    {
        $this->setApplication();
        self::assertEquals('', $this->application->getTokenUrl());
    }

    /**
     * @throws Exception
     */
    public function testGetAuthUrlWithServerUrl(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->setSettings(
                [ApplicationInterface::AUTHORIZATION_FORM => ['oauth_url' => 'https://12345.myshoptet.com/action/ApiOAuthServer/token']],
            );

        self::assertEquals(
            'https://12345.myshoptet.com/action/ApiOAuthServer/token',
            $this->application->getAuthUrlWithServerUrl($applicationInstall),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetTokenUrlWithServerUrl(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->addSettings(
                [ApplicationInterface::AUTHORIZATION_FORM => ['api_token_url' => 'https://12345.myshoptet.com/action/ApiOAuthServer/getAccessToken']],
            );

        self::assertEquals(
            'https://12345.myshoptet.com/action/ApiOAuthServer/getAccessToken',
            $this->application->getTokenUrlWithServerUrl($applicationInstall),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetWebhookSubscriptions(): void
    {
        $this->setApplication();
        self::assertNotEmpty($this->application->getWebhookSubscriptions());
    }

    /**
     * @throws Exception
     */
    public function testGetWebhookSubscribeRequestDto(): void
    {
        $this->setApplication();
        $subscription = new WebhookSubscription(
            ShoptetApplication::SHOPTET_KEY,
            'Webhook',
            'shoptet-uninstall',
            ['event' => 'unsubscription'],
        );
        $dto          = $this->application->getWebhookSubscribeRequestDto(
            (new ApplicationInstall())
                ->setSettings(
                    [
                        'clientSettings' => [
                            'token' => [
                                'access_token' => '/token.a.b.c',
                                'expires_in'   => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                            ],
                        ],
                    ],
                ),
            $subscription,
            'www.nejaka.url',
        );

        self::assertEquals('{"event":"unsubscription","url":"www.nejaka.url"}', $dto->getBody());
    }

    /**
     * @throws Exception
     */
    public function testGetWebhookSubscribeRequestDtoError(): void
    {
        $this->setApplication();

        $this->expectException(ApplicationInstallException::class);
        $subscription = new WebhookSubscription(
            ShoptetApplication::SHOPTET_KEY,
            'Webhook',
            'shoptet-uninstall',
            ['event' => 'unsubscription'],
        );
        $dto          = $this->application->getWebhookSubscribeRequestDto(
            new ApplicationInstall(),
            $subscription,
            'www.nejaka.url',
        );

        self::assertEquals('{"event":"unsubscription","url":"www.nejaka.url"}', $dto->getBody());
    }

    /**
     * @throws Exception
     */
    public function testGetWebhookUnsubscribeRequestDto(): void
    {
        $this->setApplication();
        $applicationInstall = (new ApplicationInstall())
            ->addSettings(
                [
                    'clientSettings' => [
                        'token' => [
                            'access_token' => '/token.a.b.c',
                            'expires_in'   => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                        ],
                    ],
                ],
            );
        $dto                = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            (new Webhook())->setWebhookId('id123'),
        );

        self::assertEquals('/token.a.b.c', $dto->getHeaders()['Shoptet-Access-Token']);
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, 'test', '{"data": "data"}', []),
            new ApplicationInstall(),
        );

        self::assertEquals('{"data": "data"}', $response);
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(
                200,
                'test',
                '{"data": "data"}',
                [],
            ),
        );
        self::assertTrue($response);
    }

    /**
     * @throws Exception
     */
    public function testGetApiTokenDto(): void
    {
        $this->setApplication();
        $applicationInstall = (new ApplicationInstall())
            ->addSettings(
                [
                    ApplicationInterface::AUTHORIZATION_FORM => [
                        'api_token_url'             => 'https://12345.myshoptet.com/action/ApiOAuthServer/token',
                        ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => '___access_token___'],
                    ],
                ],
            );
        $dto                = $this->application->getApiTokenDto($applicationInstall, new ProcessDto());

        self::assertEquals(
            [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ___access_token___',
                'Content-Type'  => 'application/json',
            ],
            $dto->getHeaders(),
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateDto(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->addSettings(
                [
                    ApplicationInterface::AUTHORIZATION_FORM =>
                        [
                            'api_token_url' => 'https://12345.myshoptet.com/action/ApiOAuthServer/token',
                            'oauth_url'     => 'https://12345.myshoptet.com/action/ApiOAuthServer/getAccessToken',
                        ],
                ],
            );

        $crateDto = $this->invokeMethod(
            $this->application,
            'createDto',
            [$applicationInstall, 'https://127.0.0.66/api/api/plugins/shoptet'],
        );

        self::assertEquals('https://127.0.0.66/api/api/plugins/shoptet', $crateDto->getRedirectUrl());
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyUrl(): void
    {
        $this->setApplication();

        self::assertEquals(
            'https://starting-point/topologies/123/nodes/Start/run-by-name',
            $this->application->getTopologyUrl('123'),
        );
    }

    /**
     * @throws Exception
     */
    private function setApplication(): void
    {
        $this->mockRedirect('https://12345.myshoptet.com/action/ApiOAuthServer/token', self::CLIENT_ID);
        $this->application = self::getContainer()->get('hbpf.application.shoptet');
    }

    /**
     * @param string $jsonContent
     *
     * @throws Exception
     */
    private function mockSender(string $jsonContent): void
    {
        $callback = static fn(): ResponseDto => new ResponseDto(
            200,
            'api token',
            $jsonContent,
            [
                'application' => ShoptetApplication::SHOPTET_KEY,
                'user'        => 'user',
            ],
        );

        $this->setProperty(
            self::getContainer()->get('hbpf.application.shoptet'),
            'sender',
            $this->prepareSender($callback),
        );
    }

}
