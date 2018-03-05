<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/5/17
 * Time: 1:28 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector\FacebookGetLeadformConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector\FacebookGetPageConnector;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FacebookLeadsSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads
 */
class FacebookLeadsSystem implements SystemInterface, OAuth2Interface
{

    use SystemTrait {
        toArray as parentToArray;
    }
    use AuthorizationTrait;

    private const API_URL       = 'https://graph.facebook.com/v2.11';
    private const APP_ID        = '1449914605304913';
    private const APP_SECRET    = '001b9d466b6f13d242d759cd094dccca';
    private const AUTHORIZE_URL = 'https://www.facebook.com/v2.11/dialog/oauth';
    private const TOKEN_URL     = 'https://graph.facebook.com/v2.11/oauth/access_token';

    public const   PAGE_ID   = 'page_id';
    public const   FORM_ID   = 'form_id';
    public const   FORM_LIST = 'list';
    public const   FORM_NAME = 'form_name';

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * FacebookSystem constructor.
     *
     * @param OAuth2Provider     $provider
     * @param ContainerInterface $container
     */
    public function __construct(
        OAuth2Provider $provider,
        ContainerInterface $container

    )
    {
        $this->provider = $provider;
        $this->container = $container;
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
        $arr = $this->provider->getAccessToken($this->createDto($systemInstall), $data);
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
        throw new SystemException('Facebook Leads system has not implemented "refreshToken" function.');
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
        return 'facebookleads';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Facebook Leads';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Facebook system';
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
     * @param string        $method
     *
     * @return RequestDto
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $dto = new RequestDto($method, new Uri(self::API_URL));
        $dto->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
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
        $form = new Form();

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     */
    public function getPages(SystemInstall $systemInstall, array $data): array
    {
        /** @var FacebookGetPageConnector $connector */
        $connector = $this->container->get('hbpf.connector.facebook-get-page-connector');

        return $connector->getAccounts($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     */
    public function getForms(SystemInstall $systemInstall, array $data): array
    {
        /** @var FacebookGetLeadformConnector $connector */
        $connector = $this->container->get('hbpf.connector.facebook-get-leadform-connector');

        return $connector->getLeadForms($systemInstall, $data);
    }

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

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     */
    public function saveCustomForm(SystemInstall $systemInstall, array $data = []): array
    {
        $this->setSettings($systemInstall, [SystemInstall::FORMS => $data]);

        return $this->toArray($systemInstall);
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
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    public function toArray(?SystemInstall $systemInstall = NULL): array
    {
        $arr = $this->parentToArray($systemInstall);
        if ($systemInstall && array_key_exists(SystemInstall::FORMS, $systemInstall->getSettings())) {
            $arr[SystemInstall::FORMS] = $systemInstall->getSettings()[SystemInstall::FORMS];
        }

        return $arr;
    }

}
