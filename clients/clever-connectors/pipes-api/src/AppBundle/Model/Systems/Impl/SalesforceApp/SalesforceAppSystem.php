<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAuthConnector;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;

/**
 * Class SalesforceAppSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp
 */
class SalesforceAppSystem extends PluginSystemAbstract implements OAuth2Interface
{

    protected const SWITCH_TOKEN = '';
    protected const SYNC_URL     = '';

    private const CLIENT_ID     = '3MVG95NPsF2gwOiMpyoQ03xqmzeQFuAewf2TDuTZkQ4eqKI0bQLzdyZPVbUpbex_pHfdcDGaFVK0eBn.TWAer';
    private const CLIENT_SECRET = '8796280224191647886';
    private const AUTHORIZE_URL = 'https://login.salesforce.com/services/oauth2/authorize';
    private const TOKEN_URL     = 'https://na1.salesforce.com/services/oauth2/token';
    private const API_URL       = 'instance_url';

    private const SYNC_TOPO = 'salesforceapp-sync-subscribers';
    private const SYNC_NODE = 'signal-event';

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
     * SalesforceAppSystem constructor.
     *
     * @param OAuth2Provider          $provider
     * @param SalesforceAuthConnector $connector
     * @param StartingPointHandler    $pointHandler
     */
    public function __construct(OAuth2Provider $provider, SalesforceAuthConnector $connector,
                                StartingPointHandler $pointHandler)
    {
        parent::__construct();
        $this->cmEvents     = [];
        $this->provider     = $provider;
        $this->connector    = $connector;
        $this->pointHandler = $pointHandler;
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
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function runFilterSync(SystemInstall $systemInstall, array $data): array
    {
        $distributionId = $data['distributionId'] ?? NULL;

        if ($distributionId === NULL) {
            throw new CleverConnectorsException(
                'Parameter "distributionId" is missing.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $body = ['param' => $systemInstall->getSystem(), 'user' => $systemInstall->getUser()];

        $systemInstall->setSettings(['sync' => $data]);
        $this->pointHandler->run(self::SYNC_TOPO, self::SYNC_NODE, json_encode($body));

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