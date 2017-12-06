<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/5/17
 * Time: 1:28 PM
 */

namespace AppBundle\Model\Systems\Impl\FacebookLeads;

use AppBundle\Model\Systems\Impl\FacebookLeads\Connector\FacebookGetLeadformConnector;
use AppBundle\Model\Systems\Impl\FacebookLeads\Connector\FacebookGetPageConnector;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class FacebookLeadsSystem
 *
 * @package AppBundle\Model\Systems\Impl\FacebookLeads
 */
class FacebookLeadsSystem implements SystemInterface, OAuth2Interface
{

    use SystemTrait;
    use AuthorizationTrait;

    private const API_URL = 'https://graph.facebook.com/v2.11';
    private const APP_ID        = '364762510625679';
    private const APP_SECRET    = 'e75e811167e3f129503e510968988006';
    private const AUTHORIZE_URL = 'https://www.facebook.com/v2.11/dialog/oauth';
    private const TOKEN_URL     = 'https://graph.facebook.com/v2.11/oauth/access_token';


    private const USER_ACCESS_TOKEN = 'user_access_token';
    private const PAGE_ACCESS_TOKEN = 'page_access_token';
    private const FORM_ID           = 'form_id';

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var string
     */
    private $backend;
    /**
     * @var FacebookGetPageConnector
     */
    private $facebookGetPageConnector;
    /**
     * @var FacebookGetLeadformConnector
     */
    private $facebookGetLeadformConnector;

    /**
     * FacebookSystem constructor.
     *
     * @param OAuth2Provider               $provider
     * @param string                       $backend
     * @param FacebookGetPageConnector     $facebookGetPageConnector
     * @param FacebookGetLeadformConnector $facebookGetLeadformConnector
     */
    public function __construct(OAuth2Provider $provider, string $backend,
                                FacebookGetPageConnector $facebookGetPageConnector, FacebookGetLeadformConnector $facebookGetLeadformConnector)
    {
        $this->provider                 = $provider;
        $this->backend                  = $backend;
        $this->facebookGetPageConnector = $facebookGetPageConnector;
        $this->facebookGetLeadformConnector = $facebookGetLeadformConnector;
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
        // TODO: Implement saveToken() method.
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemInstall
     */
    public function refreshToken(SystemInstall $systemInstall): SystemInstall
    {
        // TODO: Implement refreshToken() method.
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
        return 'facebook';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Facebook';
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

        $sett = $systemInstall->getSettings();

        $dto = new RequestDto($method, new Uri(self::API_URL));
        $dto->setHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
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
        $pageAccessToken = $systemInstall->getSettings()[self::PAGE_ACCESS_TOKEN] ?? NULL;

        $fieldPages = new Field(
            Field::SELECT,
            self::PAGE_ACCESS_TOKEN,
            'Page',
            $pageAccessToken,
            TRUE
        );
        $fieldPages->setAction($systemInstall, $this->backend, 'getPages');

        $fieldForms = new Field(
            Field::SELECT,
            self::FORM_ID,
            'Leads form',
            $systemInstall->getSettings()[self::FORM_ID],
            TRUE
        );
        if ($pageAccessToken) {
            $fieldForms->setAction($systemInstall, $this->backend, 'getForms');
        }

        $form = new Form();
        $form
            ->addField($fieldPages)
            ->addField($fieldForms);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getPages(SystemInstall $systemInstall): array
    {
        return $this->facebookGetPageConnector->getAccounts($this, $systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getForms(SystemInstall $systemInstall): array
    {
        return $this->facebookGetLeadformConnector->getLeadForms($this, $systemInstall);
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

}
