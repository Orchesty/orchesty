<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAccountsConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAudiencesConnector;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FacebookaudienceSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience
 */
class FacebookaudienceSystem implements OAuth2Interface
{

    use SystemTrait;
    use AuthorizationTrait;

    public const AD_ACCOUNT      = 'ad_account';
    public const CUSTOM_AUDIENCE = 'custom_audience';
    public const NEW_LIST        = 'new_list';

    public const CREATE_NEW = 'create_new'; // as a key in option list for audiences
    public const ALL        = 'all'; // as a key in option list for source distribution list

    private const APP_ID     = '198640514104383';
    private const APP_SECRET = '5bef6e7c520de76e128e68c9216e5518';

    private const API_URL       = 'https://graph.facebook.com/v2.11';
    private const AUTHORIZE_URL = 'https://www.facebook.com/v2.11/dialog/oauth';
    private const TOKEN_URL     = 'https://graph.facebook.com/v2.11/oauth/access_token';

    /**
     * @var array
     */
    private $scopes = ['manage_pages', 'ads_read', 'ads_management'];

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $backend;

    /**
     * SalesforceSystem constructor.
     *
     * @param OAuth2Provider     $provider
     * @param ContainerInterface $container
     * @param string             $backend
     */
    public function __construct(
        OAuth2Provider $provider,
        ContainerInterface $container,
        string $backend
    )
    {
        $this->provider  = $provider;
        $this->container = $container;
        $this->backend   = $backend;
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
        $sett = $systemInstall->getSettings();

        if ($systemInstall->getExpires() !== NULL) {
            $now     = (new DateTime())->setTimezone(new DateTimeZone('UTC'))->getTimestamp();
            $expires = $systemInstall->getExpires()->getTimestamp();

            if ($expires <= $now) {
                return FALSE;
            }
        }

        return !empty($sett[OAuth2Provider::ACCESS_TOKEN] ?? '');
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
        $this->provider->authorize($dto, $this->scopes);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveToken(SystemInstall $systemInstall, array $data): SystemInstall
    {
        $dto     = $this->createDto($systemInstall);
        $token   = $this->provider->getAccessToken($dto, $data);
        $expires = (new DateTime())
            ->setTimestamp($token['expires'])
            ->setTimezone(new DateTimeZone('UTC'));
        $systemInstall->setExpires($expires);

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

        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
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
            self::AD_ACCOUNT,
            'Select FB account',
            $systemInstall->getSettings()[self::AD_ACCOUNT] ?? '',
            TRUE
        );
        $field1->setAction($systemInstall, $this->backend, 'getAccounts');

        $field2 = new Field(
            Field::SELECT,
            self::CUSTOM_AUDIENCE,
            'Select your distribution list in FB',
            $systemInstall->getSettings()[self::CUSTOM_AUDIENCE] ?? '',
            TRUE
        );
        $field2->setAction($systemInstall, $this->backend, 'getAudiences');
        $field2->setDependsOn($field1);

        $field3 = new Field(
            Field::TEXT,
            self::NEW_LIST,
            'Create new list',
            $systemInstall->getSettings()[self::NEW_LIST] ?? ''
        );
        $field3->setDependsOn($field2);

        $field4 = new Field(
            Field::SELECT,
            SystemInstall::DISTRIBUTION_LIST,
            'Select source distribution list',
            $systemInstall->getSettings()[SystemInstall::DISTRIBUTION_LIST] ?? '',
            TRUE
        ); // filled by CM

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
     * @throws SystemException
     */
    public function getAccounts(SystemInstall $systemInstall): array
    {
        /** @var FacebookaudienceGetAccountsConnector $connector */
        $connector = $this->container->get('hbpf.connector.facebookaudience-get-accounts-connector');

        return $connector->getAccounts($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function getAudiences(SystemInstall $systemInstall, array $data): array
    {
        /** @var FacebookaudienceGetAudiencesConnector $connector */
        $connector = $this->container->get('hbpf.connector.facebookaudience-get-audiences-connector');

        return $connector->getAudiences($systemInstall, $data);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
        return NULL;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall
    {
        return $systemInstall;
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