<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;

/**
 * Class ShoptetApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet
 */
final class ShoptetApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

    public const string SHOPTET_KEY   = 'shoptet';
    public const string SHOPTET_URL   = 'https://api.myshoptet.com';
    public const string CANCELLED     = 'cancelled';
    public const string ESHOP_ID      = 'eshopId';
    public const string API_TOKEN_URL = 'api_token_url';

    private const string BASE_TOPOLOGY_URL   = '%s/topologies/%s/nodes/%s/run-by-name';
    private const string EXPIRES_IN          = 'expires_in';
    private const string ACCESS_TOKEN        = 'access_token';
    private const string CLIENT_SETTINGS     = 'clientSettings';
    private const string SHOPTET_WEBHOOK_URL = 'https://api.myshoptet.com/api/webhooks';
    private const string OAUTH_URL           = 'oauth_url';

    /**
     * ShoptetApplication constructor.
     *
     * @param OAuth2Provider $provider
     * @param CurlManager    $sender
     * @param string         $startingPointHost
     */
    public function __construct(
        OAuth2Provider $provider,
        private CurlManager $sender,
        private string $startingPointHost,
    )
    {
        parent::__construct($provider);
    }

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::SHOPTET_KEY;
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Shoptet';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Shoptet';
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     */
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $request = new RequestDto($this->getUri($url), $method, $dto);
        $request->setHeaders(
            [
                'Accept'               => 'application/json',
                'Content-Type'         => 'application/vnd.shoptet.v1.0',
                'Shoptet-Access-Token' => $this->getApiToken($applicationInstall, $dto),
            ],
        );
        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::ESHOP_ID, 'Eshop Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::OAUTH_URL, 'Authorization url', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::API_TOKEN_URL, 'Api token url', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return '';
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getAuthUrlWithServerUrl(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::OAUTH_URL];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getTokenUrlWithServerUrl(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::API_TOKEN_URL];
    }

    /**
     * @param string $topology
     * @param string $node
     *
     * @return string
     */
    public function getTopologyUrl(string $topology, string $node = 'Start'): string
    {
        return sprintf(self::BASE_TOPOLOGY_URL, $this->startingPointHost, $topology, $node);
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('Update Order', 'webhook', 'shoptet-order-update', ['event' => 'order:update']),
        ];
    }

    /**
     * @param ApplicationInstall  $applicationInstall
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     */
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        WebhookSubscription $subscription,
        string $url,
    ): RequestDto
    {
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            self::SHOPTET_WEBHOOK_URL,
            Json::encode(
                [
                    'event' => $subscription->getParameters()['event'],
                    'url'   => $url,
                ],
            ),
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param Webhook            $webhook
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     */
    public function getWebhookUnsubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        Webhook $webhook,
    ): RequestDto
    {
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf('%s%s', self::SHOPTET_WEBHOOK_URL, $webhook->getWebhookId()),
        );
    }

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
     */
    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
    {
        $install;

        return $dto->getBody();
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return $dto->getStatusCode() === 200;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param ProcessDtoAbstract $dto
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getApiTokenDto(ApplicationInstall $applicationInstall, ProcessDtoAbstract $dto): RequestDto
    {
        $oauthAccessToken = $this->getAccessToken($applicationInstall);

        $request = new RequestDto(
            $this->getUri($this->getTokenUrlWithServerUrl($applicationInstall)),
            CurlManager::METHOD_POST,
            $dto,
        );
        $request->setHeaders(
            [
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $oauthAccessToken),
                'Content-Type'  => 'application/json',
            ],
        );

        return $request;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string|null        $redirectUrl
     *
     * @return OAuth2Dto
     */
    protected function createDto(ApplicationInstall $applicationInstall, ?string $redirectUrl = NULL): OAuth2Dto
    {
        $dto = new OAuth2Dto(
            $applicationInstall,
            $this->getAuthUrlWithServerUrl($applicationInstall),
            $this->getTokenUrlWithServerUrl($applicationInstall),
        );
        $dto->setCustomAppDependencies($applicationInstall->getUser() ?? '', $applicationInstall->getKey() ?? '');

        if ($redirectUrl) {
            $dto->setRedirectUrl($redirectUrl);
        }

        return $dto;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param ProcessDtoAbstract $dto
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     */
    private function getApiToken(ApplicationInstall $applicationInstall, ProcessDtoAbstract $dto): string
    {
        $token = $this->getApiTokenFromSettings($applicationInstall);

        if (!$token) {
            $requestDto = $this->getApiTokenDto($applicationInstall, $dto);
            $token      = $this->sender->send($requestDto)->getJsonBody();
            $applicationInstall->addSettings(
                [
                    self::CLIENT_SETTINGS => [
                        self::TOKEN => [
                            self::ACCESS_TOKEN => $token[self::ACCESS_TOKEN],
                            self::EXPIRES_IN   =>
                                DateTimeUtils::getUtcDateTime(
                                    sprintf('now + %s sec', (string) $token[self::EXPIRES_IN]),
                                )->getTimestamp(),
                        ],
                    ],
                ],
            );
        }

        return $token[self::ACCESS_TOKEN];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return mixed[]|null
     * @throws DateTimeException
     */
    private function getApiTokenFromSettings(ApplicationInstall $applicationInstall): ?array
    {
        $token = $applicationInstall->getSettings()[self::CLIENT_SETTINGS][self::TOKEN] ?? [];

        if (isset($token[self::ACCESS_TOKEN]) && isset($token[self::EXPIRES_IN])
            && $token[self::EXPIRES_IN] > DateTimeUtils::getUtcDateTime()->getTimestamp()) {
            return $token;
        }

        return NULL;
    }

}
