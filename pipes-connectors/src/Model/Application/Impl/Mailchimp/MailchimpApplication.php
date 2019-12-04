<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp;

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
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;

/**
 * Class MailchimpApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp
 */
class MailchimpApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

    public const MAILCHIMP_URL            = 'https://login.mailchimp.com/oauth2/authorize';
    public const MAILCHIMP_DATACENTER_URL = 'https://login.mailchimp.com';
    public const AUDIENCE_ID              = 'audience_id';
    public const TOKEN_URL                = 'https://login.mailchimp.com/oauth2/token';
    public const API_KEYPOINT             = 'api_keypoint';
    public const SEGMENT_ID               = 'segment_id';

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * MailchimpApplication constructor.
     *
     * @param OAuth2Provider       $provider
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(OAuth2Provider $provider, CurlManagerInterface $curlManager)
    {
        parent::__construct($provider);
        $this->curlManager = $curlManager;
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
        return 'mailchimp';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Mailchimp';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Mailchimp v3';
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return self::MAILCHIMP_URL;
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
                'Authorization' => sprintf('OAuth %s', $this->getAccessToken($applicationInstall)),
            ]
        );

        if (!empty($data)) {
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
        $form->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, self::AUDIENCE_ID, 'Audience Id', NULL, TRUE));

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
     * @param ApplicationInstall $applicationInstall
     * @param array              $token
     *
     * @return OAuth2ApplicationInterface
     * @throws ApplicationInstallException
     * @throws AuthorizationException
     * @throws CurlException
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token
    ): OAuth2ApplicationInterface
    {
        parent::setAuthorizationToken($applicationInstall, $token);

        $applicationInstall->setSettings(
            [
                self::API_KEYPOINT => $this->getApiEndpoint($applicationInstall),
            ]
        );

        return $this;

    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getApiEndpoint(ApplicationInstall $applicationInstall): string
    {
        $return = $this->curlManager->send(
            $this->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_GET,
                sprintf('%s/oauth2/metadata', self::MAILCHIMP_DATACENTER_URL)
            )
        );

        return $return->getJsonBody()['api_endpoint'];
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('Create User', 'starting-point', '', ['name' => 'subscribe']),
            new WebhookSubscription('Update User', 'starting-point', '', ['name' => 'upemail']),
            new WebhookSubscription('Delete User', 'starting-point', '', ['name' => 'unsubscribe']),
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
        return $this->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf(
                '%s/3.0/lists/%s/webhooks',
                $applicationInstall->getSettings()[self::API_KEYPOINT],
                $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::AUDIENCE_ID]
            ),
            Json::encode(
                [
                    'url'     => $url,
                    'events'  => [
                        $subscription->getParameters()['name'] => TRUE,
                    ],
                    'sources' => [
                        'user'  => TRUE,
                        'admin' => TRUE,
                        'api'   => TRUE,
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
                '%s/3.0/lists/%s/webhooks/%s',
                $applicationInstall->getSettings()[self::API_KEYPOINT],
                $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::AUDIENCE_ID],
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
        $install;

        return Json::decode($dto->getBody())['id'];
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return $dto->getStatusCode() === 204;
    }

}

