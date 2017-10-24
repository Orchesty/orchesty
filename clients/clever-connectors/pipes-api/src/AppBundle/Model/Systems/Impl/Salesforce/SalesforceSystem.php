<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce;

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.10.17
 * Time: 10:36
 */

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class SalesforceSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce
 */
class SalesforceSystem implements OAuth2Interface
{

    use AuthorizationTrait;

    private const CLIENT_ID     = '3MVG9g9rbsTkKnAW3CCxLnJW_55kKZ67XIZboIqeXL93kTR7hQMuU8ZPRgtzK79H0Xo44sZIqZENW.POc.lpJ';
    private const CLIENT_SECRET = '9190968633171454987';
    private const AUTHORIZE_URL = 'https://login.salesforce.com/services/oauth2/authorize';
    private const TOKEN_URL     = 'https://na1.salesforce.com/services/oauth2/token';

    private const API_URL = 'instance_url';

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * SalesforceSystem constructor.
     *
     * @param OAuth2Provider $provider
     */
    public function __construct(OAuth2Provider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'salesforce';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Salesforce';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Salesforce descr.';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo.png';
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::OAUTH2;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::CRON;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        if (
            isset($systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN]) &&
            !empty($systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN])
        ) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param SystemInstall $systemInstall
     */
    public function authorize(SystemInstall $systemInstall): void
    {
        $dto = $this->createDto($systemInstall);
        $this->provider->authorize($dto);
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
        $systemInstall->setExpires(NULL);

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
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => sprintf('Bearer %s', $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN]),
        ];

        $dto = new RequestDto($method, new Uri($systemInstall->getSettings()[self::API_URL]));
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
        return [];
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

        $dto = new OAuth2Dto(self::CLIENT_ID, self::CLIENT_SECRET, $redirectUrl, self::AUTHORIZE_URL, self::TOKEN_URL);
        $dto->setCustomAppDependencies($systemInstall->getUser(), $systemInstall->getSystem());

        return $dto;
    }

}