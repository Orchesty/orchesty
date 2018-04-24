<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/20/17
 * Time: 3:03 PM
 */
class QuickbooksSystem implements OAuth2Interface, CMEventSystemInterface
{

    use SystemTrait;
    use AuthorizationTrait;
    use CMEventSystemTrait;

    private const CLIENT_ID     = 'Q0VXajrrB3de6wDpxacbHRq1u1qWz4QHPjt5ypHzd49IPr36xa';
    private const CLIENT_SECRET = 'NUVTEc0Q13kP3tD2lHmwfHfuJplnpYwxsRA7FXJ8';
    private const AUTHORIZE_URL = 'https://appcenter.intuit.com/connect/oauth2';
    private const TOKEN_URL     = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    private const API_URL       = 'https://sandbox-quickbooks.api.intuit.com/v3/company/%s/';
    private const REALM_ID_KEY  = 'realmId';

    /**
     * @var array
     */
    private $scopes = [
        'com.intuit.quickbooks.accounting',
    ];

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
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
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
     * @return string
     */
    public function getKey(): string
    {
        return 'quickbooks';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Quickbooks';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Quickbooks system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        return !empty($systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN]);
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
        $arr                     = $this->provider->getAccessToken($this->createDto($systemInstall), $data);
        $arr[self::REALM_ID_KEY] = $data[self::REALM_ID_KEY];
        $expires                 = new DateTime();
        $expires->setTimestamp($arr['expires']);
        $systemInstall->setExpires($expires);
        $this->setSettings($systemInstall, $arr);

        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemInstall
     * @throws SystemException
     */
    public function refreshToken(SystemInstall $systemInstall): SystemInstall
    {
        $this->continueOnAuthorized($systemInstall);

        $settings = $systemInstall->getSettings();
        $dto      = $this->createDto($systemInstall);
        $dto->setCustomAppDependencies($systemInstall->getUser(), $this->getKey());

        $this->provider->refreshAccessToken(
            $dto,
            [OAuth2Provider::REFRESH_TOKEN => $settings[OAuth2Provider::REFRESH_TOKEN]]
        );

        return $systemInstall;
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

        $sett = $systemInstall->getSettings();

        $url = sprintf(self::API_URL, $sett[self::REALM_ID_KEY]);
        $dto = new RequestDto($method, new Uri($url));
        $dto->setHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $sett[OAuth2Provider::ACCESS_TOKEN],
        ]);

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
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field2 = new Field(
            Field::SELECT,
            SystemInstall::SELECT_LIST,
            'Distribution list',
            $this->prepareValue(SystemInstall::SELECT_LIST, $systemInstall->getSettings())
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|null
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface
    {
        return NULL;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto
     */
    public function getLimit(SystemInstall $systemInstall): SystemLimitDto
    {
        return new SystemLimitDto($systemInstall, SystemLimitDto::LIMIT_FOR_SYSTEM, 60, 500, new DateTime());
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

        $dto = new OAuth2Dto(self::CLIENT_ID, self::CLIENT_SECRET, $redirectUrl, self::AUTHORIZE_URL, self::TOKEN_URL);
        $dto->setCustomAppDependencies($systemInstall->getUser(), $systemInstall->getSystem());

        return $dto;
    }

}