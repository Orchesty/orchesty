<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;

/**
 * Class HubspotApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot
 */
class HubspotApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

    public const  BASE_URL    = 'https://api.hubapi.com';
    public const  HUBSPOT_URL = 'https://app.hubspot.com/oauth/authorize';
    public const  TOKEN_URL   = 'https://api.hubapi.com/oauth/v1/token';
    private const SCOPES      = ['contacts'];

    private const APP_ID = 'app_id';

    /**
     * @var CurlManagerInterface
     */
    private $manager;

    /**
     * HubspotApplication constructor.
     *
     * @param OAuth2Provider       $provider
     * @param CurlManagerInterface $manager
     */
    public function __construct(OAuth2Provider $provider, CurlManagerInterface $manager)
    {
        parent::__construct($provider);

        $this->manager = $manager;
    }

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'hubspot';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Hubspot';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Hubspot v1';
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return self::HUBSPOT_URL;
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return self::TOKEN_URL;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $scopes
     * @param string             $separator
     */
    public function authorize(
        ApplicationInstall $applicationInstall,
        array $scopes = [],
        string $separator = ScopeFormatter::COMMA
    ): void
    {
        $scopes;
        $separator;

        parent::authorize($applicationInstall, self::SCOPES, ScopeFormatter::SPACE);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getRequestDto(
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL
    ): RequestDto
    {
        $request = new RequestDto($method, $this->getUri($url));
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
            ]
        );

        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return Form
     * @throws ApplicationInstallException
     */
    public function getSettingsForm(): Form
    {
        $form = new Form();
        $form
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', TRUE))
            ->addField(new Field(Field::TEXT, self::APP_ID, 'Application Id', NULL, TRUE));

        return $form;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        try {
            $this->getAccessToken($applicationInstall);

            return TRUE;
        } catch (ApplicationInstallException $e) {

            return FALSE;
        }
    }

    /**
     * @return array
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('Create Contact', 'starting-point', '', ['name' => 'contact.creation']),
            new WebhookSubscription('Delete Contact', 'starting-point', '', ['name' => 'contact.deletion']),
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
     */
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        WebhookSubscription $subscription,
        string $url
    ): RequestDto
    {
        $this->manager->send(
            $this->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_PUT,
                sprintf(
                    '/webhooks/v1/%s/settings',
                    $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::APP_ID]
                ),
                Json::encode(
                    [
                        'webhookUrl'            => $url,
                        'maxConcurrentRequests' => 100,
                    ]
                )
            )
        );

        return $this->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf(
                '/webhooks/v1/%s/subscriptions',
                $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::APP_ID]
            ),
            Json::encode(
                [
                    'subscriptionDetails' => [
                        'subscriptionType' => $subscription->getParameters()['name'],
                        'propertyName'     => 'email',
                        'enabled'          => TRUE,
                    ],
                ]
            )
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $id
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(ApplicationInstall $applicationInstall, string $id): RequestDto
    {
        return $this->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_DELETE,
            sprintf(
                '/webhooks/v1/%s/subscriptions/%s',
                $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::APP_ID],
                $id
            )
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
        $dto;
        $install;

        // TODO: This oauth-token does not have proper permissions! (requires all of [developers-access])"

        return (string) mt_rand(PHP_INT_MIN, PHP_INT_MAX);
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        $dto;

        // TODO: This oauth-token does not have proper permissions! (requires all of [developers-access])"

        return TRUE;
    }

}
