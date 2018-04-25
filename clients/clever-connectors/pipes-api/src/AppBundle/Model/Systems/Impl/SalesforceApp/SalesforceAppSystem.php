<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\CustomFieldsConnector\CMGetCustomFieldsConnector;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAuthConnector;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\InnerRequestUtils;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;

/**
 * Class SalesforceAppSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp
 */
class SalesforceAppSystem implements OAuth2Interface, CMEventSystemInterface
{

    use SystemTrait;
    use CMEventSystemTrait;
    use AuthorizationTrait;

    public const DL_ID     = 'distributionId';
    public const FILTER_ID = 'filterId';
    public const API_URL   = 'instance_url';

    protected const SWITCH_TOKEN = '';
    protected const SYNC_URL     = '';

    private const CLIENT_ID     = '3MVG95NPsF2gwOiMpyoQ03xqmzeQFuAewf2TDuTZkQ4eqKI0bQLzdyZPVbUpbex_pHfdcDGaFVK0eBn.TWAer';
    private const CLIENT_SECRET = '8796280224191647886';
    private const AUTHORIZE_URL = 'https://login.salesforce.com/services/oauth2/authorize';
    private const TOKEN_URL     = 'https://na1.salesforce.com/services/oauth2/token';

    private const SYNC_TOPO = 'salesforceapp-sync-subscribers';
    private const SYNC_NODE = 'signal-event';

    private const LIMIT_TIME = 86400;
    private const KEY_DAILY  = 'DailyApiRequests';
    private const KEY_MAX    = 'Max';

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var SalesforceAuthConnector
     */
    private $connector;

    /**
     * @var StartingPointHandler
     */
    private $pointHandler;

    /**
     * @var SystemLimitManager
     */
    private $limitManager;

    /**
     * @var CMGetCustomFieldsConnector
     */
    private $fieldsConnector;

    /**
     * SalesforceAppSystem constructor.
     *
     * @param OAuth2Provider             $provider
     * @param SalesforceAuthConnector    $connector
     * @param StartingPointHandler       $pointHandler
     * @param SystemLimitManager         $limitManager
     * @param CMGetCustomFieldsConnector $fieldsConnector
     */
    public function __construct(
        OAuth2Provider $provider,
        SalesforceAuthConnector $connector,
        StartingPointHandler $pointHandler,
        SystemLimitManager $limitManager,
        CMGetCustomFieldsConnector $fieldsConnector
    )
    {
        $this->cmEvents        = [];
        $this->provider        = $provider;
        $this->connector       = $connector;
        $this->pointHandler    = $pointHandler;
        $this->limitManager    = $limitManager;
        $this->fieldsConnector = $fieldsConnector;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'salesforceapp';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'SalesForce App';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'SalesForce App ...';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
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
     * @throws SystemException
     * @throws ConnectorException
     */
    public function saveToken(SystemInstall $systemInstall, array $data): SystemInstall
    {
        $dto   = $this->createDto($systemInstall);
        $token = $this->provider->getAccessToken($dto, $data);
        $systemInstall->setExpires(NULL);

        $systemInstall = $this->setSettings($systemInstall, $token);

        $this->connector->sendAuthorizeConfirm($systemInstall, $this);

        return $systemInstall;
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
     * @return RequesterInterface|null ?RequesterInterface
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface
    {
        return NULL;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
        $settings = $systemInstall->getSettings();
        if (array_key_exists(SystemInstall::SYSTEM_LIMITS, $settings)) {
            $systemLimits = $settings[SystemInstall::SYSTEM_LIMITS];

            if (array_key_exists(SystemInstall::SYSTEM_LIMIT_VALUE, $systemLimits)) {
                return new SystemLimitDto(
                    $systemInstall,
                    SystemLimitDto::LIMIT_FOR_USER,
                    self::LIMIT_TIME,
                    $systemLimits[SystemInstall::SYSTEM_LIMIT_VALUE],
                    $systemLimits[SystemInstall::SYSTEM_LIMIT_UPDATE] ?? NULL
                );
            }
        }

        return NULL;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     * @throws CleverConnectorsException
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall
    {
        if (array_key_exists(self::KEY_DAILY, $data) && array_key_exists(self::KEY_MAX, $data[self::KEY_DAILY])) {
            $limit = $data[self::KEY_DAILY][self::KEY_MAX];
            $this->setSettings($systemInstall, [
                SystemInstall::SYSTEM_LIMITS => [
                    SystemInstall::SYSTEM_LIMIT_VALUE  => $limit,
                    SystemInstall::SYSTEM_LIMIT_UPDATE => new DateTime(),
                ],
            ]);

            return $systemInstall;
        }

        throw new CleverConnectorsException(
            sprintf('Missing %s.%s value in response body', self::KEY_DAILY, self::KEY_MAX),
            CleverConnectorsException::MISSING_DATA
        );
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
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function runFilterSync(SystemInstall $systemInstall, array $data): array
    {
        $distributionId = $data[self::DL_ID] ?? NULL;
        $filterId       = $data[self::FILTER_ID] ?? NULL;

        if ($distributionId === NULL || $filterId === NULL) {
            throw new CleverConnectorsException(
                'Parameter "distributionId" or "filterId" is missing.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $request = InnerRequestUtils::getRequest($systemInstall, $data);
        $request->setMethod(CurlManager::METHOD_POST);
        $this->limitManager->addSystemLimitToRequestHeaders($request->headers, $this, $systemInstall);

        $this->pointHandler->runWithRequest($request, self::SYNC_TOPO, self::SYNC_NODE);

        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getCustomFields(SystemInstall $systemInstall, array $data): array
    {
        $dto = new ProcessDto();
        $dto
            ->setData(json_encode($data))
            ->setHeaders([
                CMHeaders::createKey(CMHeaders::GUID)  => $systemInstall->getUser(),
                CMHeaders::createKey(CMHeaders::TOKEN) => $systemInstall->getToken(),
            ]);

        return $this->fieldsConnector->getCustomFieldsArray($dto);
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
        $dto         = new OAuth2Dto(
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            $redirectUrl,
            self::AUTHORIZE_URL,
            self::TOKEN_URL
        );

        $dto->setCustomAppDependencies($systemInstall->getUser(), $systemInstall->getSystem());

        return $dto;
    }

}