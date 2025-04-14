<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
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
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class MailchimpApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp
 */
final class MailchimpApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

    public const string MAILCHIMP_URL            = 'https://login.mailchimp.com/oauth2/authorize';
    public const string MAILCHIMP_DATACENTER_URL = 'https://login.mailchimp.com';
    public const string AUDIENCE_ID              = 'audience_id';
    public const string TOKEN_URL                = 'https://login.mailchimp.com/oauth2/token';
    public const string API_KEYPOINT             = 'api_keypoint';
    public const string SEGMENT_ID               = 'segment_id';

    /**
     * MailchimpApplication constructor.
     *
     * @param OAuth2Provider       $provider
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(OAuth2Provider $provider, private CurlManagerInterface $curlManager)
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
        return 'mailchimp';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
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
     * @param ProcessDtoAbstract $dto
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
                'Accept'        => 'application/json',
                'Authorization' => sprintf('OAuth %s', $this->getAccessToken($applicationInstall)),
                'Content-Type'  => 'application/json',
            ],
        );

        if ($data !== NULL) {
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
        $form->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, self::AUDIENCE_ID, 'Audience Id', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $token
     *
     * @return OAuth2ApplicationInterface
     * @throws ApplicationInstallException
     * @throws AuthorizationException
     * @throws CurlException
     * @throws JsonException
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token,
    ): OAuth2ApplicationInterface
    {
        parent::setAuthorizationToken($applicationInstall, $token);

        $applicationInstall->addSettings(
            [
                self::API_KEYPOINT => $this->getApiEndpoint($applicationInstall),
            ],
        );

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws JsonException
     */
    public function getApiEndpoint(ApplicationInstall $applicationInstall): string
    {
        $return = $this->curlManager->send(
            $this->getRequestDto(
                new ProcessDto(),
                $applicationInstall,
                CurlManager::METHOD_GET,
                sprintf('%s/oauth2/metadata', self::MAILCHIMP_DATACENTER_URL),
            ),
        );

        return $return->getJsonBody()['api_endpoint'] ?? '';
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
        string $url,
    ): RequestDto
    {
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf(
                '%s/3.0/lists/%s/webhooks',
                $applicationInstall->getSettings()[self::API_KEYPOINT],
                $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::AUDIENCE_ID],
            ),
            Json::encode(
                [
                    'events'  => [
                        $subscription->getParameters()['name'] => TRUE,
                    ],
                    'sources' => [
                        'admin' => TRUE,
                        'api'   => TRUE,
                        'user'  => TRUE,
                    ],
                    'url'     => $url,
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
     */
    public function getWebhookUnsubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        Webhook $webhook,
    ): RequestDto
    {
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_DELETE,
            sprintf(
                '%s/3.0/lists/%s/webhooks/%s',
                $applicationInstall->getSettings()[self::API_KEYPOINT],
                $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::AUDIENCE_ID],
                $webhook->getWebhookId(),
            ),
        );
    }

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
     * @throws JsonException
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
