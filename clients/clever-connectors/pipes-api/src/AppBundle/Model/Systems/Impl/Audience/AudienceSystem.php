<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Audience;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class AudienceSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Audience
 */
class AudienceSystem implements OAuth1Interface
{

    use SystemTrait;
    use AuthorizationTrait;

    private const CLIENT_ID     = '';
    private const CLIENT_SECRET = '';
    private const AUTHORIZE_URL = '';
    private const TOKEN_URL     = '';

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
        return self::OAUTH;
    }

    /**
     * @param SystemInstall $systemInstall
     */
    public function authorize(SystemInstall $systemInstall): void
    {
        $dto = $this->createDto($systemInstall);
        $this->provider->authorize($dto); // TODO scopes?
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
        return 'audience';
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
     * @param string        $method
     *
     * @return RequestDto
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        // TODO: Implement getRequestDto() method.
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        // TODO: Implement getSettingFields() method.
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