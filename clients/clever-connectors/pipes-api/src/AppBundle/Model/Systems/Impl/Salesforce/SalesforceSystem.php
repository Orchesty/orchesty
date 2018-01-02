<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce;

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.10.17
 * Time: 10:36
 */

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class SalesforceSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce
 */
class SalesforceSystem implements OAuth2Interface, CMEventSystemInterface
{

    use SystemTrait;
    use CMEventSystemTrait;
    use AuthorizationTrait;

    private const CLIENT_ID     = 'client_id';
    private const CLIENT_SECRET = 'client_secret';
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
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));

        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT,
            $this->getKey());
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
        $sett = $systemInstall->getSettings();

        $field1 = new Field(
            Field::TEXT,
            self::CLIENT_ID,
            'Client ID',
            $this->prepareValue(self::CLIENT_ID, $sett),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            self::CLIENT_SECRET,
            'Client secret',
            $this->prepareValue(self::CLIENT_SECRET, $sett),
            TRUE
        );

        $field3 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'Unsubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field5 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard Bounce event',
            $systemInstall->isEventHardBounce()
        );

        $field6 = new Field(
            Field::SELECT,
            SystemInstall::SELECT_LIST,
            'Distribution list',
            $this->prepareValue(SystemInstall::SELECT_LIST, $systemInstall->getSettings())
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4)
            ->addField($field5)
            ->addField($field6);

        return $form->toArray();
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
        $sett = $systemInstall->getSettings();
        if (!array_key_exists(self::CLIENT_ID, $sett)
            || !array_key_exists(self::CLIENT_SECRET, $sett)) {
            throw new SystemException::MISSING_DATA (
                'Missing Client Id or Client secret in settings.'
            );
        }

        $redirectUrl = AuthorizationUtils::generateUrl();

        $dto = new OAuth2Dto($sett[self::CLIENT_ID], $sett[self::CLIENT_SECRET], $redirectUrl, self::AUTHORIZE_URL, self::TOKEN_URL);
        $dto->setCustomAppDependencies($systemInstall->getUser(), $systemInstall->getSystem());

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|null ?RequesterInterface
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface
    {
        return NULL;
    }

}