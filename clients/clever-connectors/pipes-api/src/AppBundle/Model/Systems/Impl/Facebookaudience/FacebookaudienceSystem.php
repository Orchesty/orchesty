<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAccountsConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAudiencesConnector;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class FacebookaudienceSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience
 */
class FacebookaudienceSystem implements OAuth2Interface
{

    use SystemTrait;
    use AuthorizationTrait;

    public const AD_ACCOUNT_ID        = 'ad_account_id';
    public const CUSTOM_AUDIENCE_ID   = 'custom_audience_id';
    public const NEW_LIST             = 'new_list';
    public const DISTRIBUTION_LIST_ID = 'distribution_list_id';

    private const APP_ID     = '1449914605304913';
    private const APP_SECRET = '001b9d466b6f13d242d759cd094dccca';

    private const API_URL       = 'https://graph.facebook.com/v2.11';
    private const AUTHORIZE_URL = '';
    private const TOKEN_URL     = 'https://graph.facebook.com/v2.11/oauth/access_token?client_id=%s&client_secret=%s&grant_type=client_credentials';

    private $scopes = [''];

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var FacebookaudienceGetAccountsConnector
     */
    private $accountsConnector;

    /**
     * @var FacebookaudienceGetAudiencesConnector
     */
    private $audiencesConnector;

    /**
     * @var string
     */
    private $backend;

    /**
     * SalesforceSystem constructor.
     *
     * @param OAuth2Provider                        $provider
     * @param FacebookaudienceGetAccountsConnector  $accountsConnector
     * @param FacebookaudienceGetAudiencesConnector $audiencesConnector
     * @param string                                $backend
     */
    public function __construct(
        OAuth2Provider $provider,
        FacebookaudienceGetAccountsConnector $accountsConnector,
        FacebookaudienceGetAudiencesConnector $audiencesConnector,
        string $backend
    )
    {
        $this->provider           = $provider;
        $this->accountsConnector  = $accountsConnector;
        $this->audiencesConnector = $audiencesConnector;
        $this->backend            = $backend;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::CRON;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'facebookaudience';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Facebook Audience';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Facebook Audience';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        // TODO: Implement isAuthorized() method.
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::OAUTH2;
    }

    /**
     * @param SystemInstall $systemInstall
     */
    public function authorize(SystemInstall $systemInstall): void
    {
        $dto = $this->createDto($systemInstall);
        $this->provider->authorize($dto, $this->scopes); // TODO scopes
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveToken(SystemInstall $systemInstall, array $data): SystemInstall
    {
        $dto   = $this->createDto($systemInstall);
        $token = $this->provider->getAccessToken($dto, $data);
        $systemInstall->setExpires(NULL); // TODO check

        return $this->setSettings($systemInstall, $token);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemInstall
     */
    public function refreshToken(SystemInstall $systemInstall): SystemInstall
    {
        $dto   = $this->createDto($systemInstall);
        $token = $this->provider->refreshAccessToken($dto, $systemInstall->getSettings());

        return $this->setSettings($systemInstall, $token);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        // TODO

        $headers = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => sprintf('Bearer %s', $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN]),
        ];

        $dto = new RequestDto($method, new Uri(self::API_URL));
        $dto->setHeaders(array_merge($headers, $dto->getHeaders()));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $field1 = new Field(
            Field::SELECT,
            self::AD_ACCOUNT_ID,
            'Select FB account',
            $systemInstall->getSettings()[self::AD_ACCOUNT_ID],
            TRUE
        );
        $field1->setAction($systemInstall, $this->backend, 'getAccounts');

        $field2 = new Field(
            Field::SELECT,
            self::CUSTOM_AUDIENCE_ID,
            'Select your distribution list in FB',
            $systemInstall->getSettings()[self::CUSTOM_AUDIENCE_ID],
            TRUE
        );
        $field2->setAction($systemInstall, $this->backend, 'getAudiences');
        $field2->setDependsOn($field1);

        $field3 = new Field(
            Field::TEXT,
            self::NEW_LIST,
            'Create new list',
            $systemInstall->getSettings()[self::NEW_LIST]
        );
        $field3->setDependsOn($field2);

        $field4 = new Field(
            Field::SELECT,
            self::DISTRIBUTION_LIST_ID,
            'Select source distribution list',
            $systemInstall->getSettings()[self::DISTRIBUTION_LIST_ID],
            TRUE
        ); // will be filled by CM

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getAccounts(SystemInstall $systemInstall): array
    {
        return $this->accountsConnector->getAccounts($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getAudiences(SystemInstall $systemInstall): array
    {
        return $this->audiencesConnector->getAudiences($systemInstall);
    }

    /**
     * ---------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     *
     * @return OAuth2Dto
     */
    private function createDto(SystemInstall $systemInstall): OAuth2Dto
    {
        $redirectUrl = AuthorizationUtils::generateUrl();

        $dto = new OAuth2Dto(self::APP_ID, self::APP_SECRET, $redirectUrl, self::AUTHORIZE_URL, self::TOKEN_URL);
        $dto->setCustomAppDependencies($systemInstall->getUser(), $systemInstall->getSystem());

        return $dto;
    }

}