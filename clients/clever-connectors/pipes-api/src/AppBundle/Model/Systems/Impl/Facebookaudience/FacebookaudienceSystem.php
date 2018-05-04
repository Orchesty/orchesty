<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\AdTypeEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceDeleteAudienceConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAdBudgetConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAudiencesConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetPagesConnector;
use CleverConnectors\AppBundle\Model\Systems\SystemTopologyRunner;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Repository\AudienceMirrorRepository;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\InnerRequestUtils;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use LogicException;

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

    private const API_URL       = 'https://graph.facebook.com/v2.12';
    private const AUTHORIZE_URL = 'https://www.facebook.com/v2.12/dialog/oauth';
    private const TOKEN_URL     = 'https://graph.facebook.com/v2.12/oauth/access_token';

    /**
     * @var array
     */
    private $scopes = ['manage_pages', 'ads_read', 'ads_management'];

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var string
     */
    private $backend;

    /**
     * @var SystemTopologyRunner
     */
    private $runner;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var FacebookaudienceGetAudiencesConnector
     */
    private $audienceConnector;

    /**
     * @var FacebookaudienceGetPagesConnector
     */
    private $pageConnector;

    /**
     * @var FacebookaudienceGetAdBudgetConnector
     */
    private $budgetConnector;

    /**
     * @var FacebookaudienceDeleteAudienceConnector
     */
    private $deleteAudienceConnector;

    /**
     * SalesforceSystem constructor.
     *
     * @param OAuth2Provider                          $provider
     * @param string                                  $backend
     * @param SystemTopologyRunner                    $runner
     * @param DocumentManager                         $dm
     * @param FacebookaudienceGetAudiencesConnector   $audienceConnector
     * @param FacebookaudienceGetPagesConnector       $pageConnector
     * @param FacebookaudienceGetAdBudgetConnector    $budgetConnector
     * @param FacebookaudienceDeleteAudienceConnector $deleteAudienceConnector
     */
    public function __construct(
        OAuth2Provider $provider,
        string $backend,
        SystemTopologyRunner $runner,
        DocumentManager $dm,
        FacebookaudienceGetAudiencesConnector $audienceConnector,
        FacebookaudienceGetPagesConnector $pageConnector,
        FacebookaudienceGetAdBudgetConnector $budgetConnector,
        FacebookaudienceDeleteAudienceConnector $deleteAudienceConnector
    )
    {
        $this->provider                = $provider;
        $this->backend                 = $backend;
        $this->runner                  = $runner;
        $this->dm                      = $dm;
        $this->audienceConnector       = $audienceConnector;
        $this->pageConnector           = $pageConnector;
        $this->budgetConnector         = $budgetConnector;
        $this->deleteAudienceConnector = $deleteAudienceConnector;
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
            'Content-Type' => 'multipart/form-data',
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
     * @throws CleverConnectorsException
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $field1 = new Field(
            Field::SELECT,
            self::AD_ACCOUNT,
            'Select FB account',
            $this->prepareValue(self::AD_ACCOUNT, $systemInstall->getSettings()),
            TRUE
        );
        $field1->setAction($systemInstall, $this->backend, 'getAccounts');

        $form = new Form();
        $form->addField($field1);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function getAudiences(SystemInstall $systemInstall, array $data): array
    {
        return $this->audienceConnector->getAudiences($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CurlException
     * @throws SystemException
     */
    public function getPages(SystemInstall $systemInstall, array $data): array
    {
        return $this->pageConnector->getPages($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function getBudget(SystemInstall $systemInstall, array $data): array
    {
        if (!array_key_exists('ad_id', $data)) {
            throw new LogicException(
                'Missing required field [ad_id].'
            );
        }

        return $this->budgetConnector->getAdBudget($systemInstall, $data['ad_id']);
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
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function createAd(SystemInstall $systemInstall, array $data): array
    {
        $req = InnerRequestUtils::getRequest($systemInstall, $data);
        $this->runner->runTopologies(TopologyNameUtils::CREATE_AD, $systemInstall, $this, $req);

        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CurlException
     * @throws SystemException
     * @throws MappingException
     * @throws LockException
     */
    public function deleteAd(SystemInstall $systemInstall, array $data): array
    {
        $mirr = NULL;
        /** @var AudienceMirrorRepository $repo */
        $repo = $this->dm->getRepository(AudienceMirror::class);
        if (array_key_exists('mirror_id', $data) // Received by AdFacade -> deleteAd
            && array_key_exists('ad_id', $data)
        ) {
            /** @var AudienceMirror $mirr */
            $mirr = $repo->find($data['mirror_id']);
            $mirr->removeAdId($data['ad_id']);
            if (!empty($mirr->getAdsId())) {
                $mirr = NULL;
            }
            $this->dm->flush();
        } else if (array_key_exists('audience_id', $data)) { // Received by AudienceFacade -> deleteAudience
            $mirr = $repo->getByAudience($data['audience_id'], AdTypeEnum::FB);
        }

        if ($mirr) {
            return [
                $this->deleteAudienceConnector->deleteAudience(
                    $systemInstall,
                    $mirr->getSystemAudienceId(),
                    $mirr->getId()),
            ];
        }

        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function syncAudience(SystemInstall $systemInstall, array $data): array
    {
        if (!array_key_exists('audience', $data)) {
            throw new LogicException('Missing required field [audience].');
        }

        $req = InnerRequestUtils::getRequest($systemInstall, $data);
        $this->runner->runTopologies(TopologyNameUtils::CREATE_AUDIENCE, $systemInstall, $this, $req);

        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function createAudience(SystemInstall $systemInstall, array $data): array
    {
        if (!array_key_exists('audience', $data)) {
            throw new LogicException('Missing required field [audience].');
        }

        /** @var AudienceMirrorRepository $repo */
        $repo = $this->dm->getRepository(AudienceMirror::class);
        /** @var AudienceMirror $mirr */
        $mirr = $repo->getByAudience($data['audience']['id'] ?? '', $data['type']);
        if ($mirr) {
            $data['audience_id'] = $mirr->getSystemAudienceId();
            $data['mirror_id']   = $mirr->getId();
            unset($data['audience']);

            return $this->createAd($systemInstall, $data);
        } else {
            $req = InnerRequestUtils::getRequest($systemInstall, $data);
            $req->headers->add([
                CMHeaders::createKey('createAd') => TRUE,
            ]);
            $this->runner->runTopologies(TopologyNameUtils::CREATE_AUDIENCE, $systemInstall, $this, $req);
        }

        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function checkAdStatus(SystemInstall $systemInstall, array $data): array
    {
        if (!array_key_exists('client_id', $data)) {
            throw new LogicException(
                'Missing required field [client_id].'
            );
        }

        $req = InnerRequestUtils::getRequest($systemInstall, [
            'client_id' => $data['client_id'],
        ]);
        $this->runner->runTopologies(TopologyNameUtils::CHECK_STATUS, $systemInstall, $this, $req);

        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws DocumentNotFoundException
     */
    public function addEmails(SystemInstall $systemInstall, array $data): array
    {
        $this->updateAudience($systemInstall, $data, 'create');

        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws DocumentNotFoundException
     */
    public function removeEmails(SystemInstall $systemInstall, array $data): array
    {
        $this->updateAudience($systemInstall, $data, 'delete');

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

        $dto = new OAuth2Dto(self::APP_ID, self::APP_SECRET, $redirectUrl, self::AUTHORIZE_URL, self::TOKEN_URL);
        $dto->setCustomAppDependencies($systemInstall->getUser(), $systemInstall->getSystem());

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     * @param string        $key
     *
     * @throws CleverConnectorsException
     * @throws DocumentNotFoundException
     */
    private function updateAudience(SystemInstall $systemInstall, array $data, string $key): void
    {
        if (!array_key_exists('emails', $data)
            || !array_key_exists('audience_id', $data)
        ) {
            throw new LogicException(
                'Missing one of required fields [emails, audience_id].'
            );
        }

        $emls = [];
        foreach ($data['emails'] as $email) {
            $emls[] = hash('sha256', $email);
        }

        /** @var AudienceMirrorRepository $repo */
        $repo = $this->dm->getRepository(AudienceMirror::class);
        $mirr = $repo->getByAudience($data['audience_id'], AdTypeEnum::FB);
        if (!$mirr) {
            throw new DocumentNotFoundException(
                sprintf('Mirror for audience with id [%s] was not found.', $data['audience_id'])
            );
        }

        $body       = [
            Comparator::KEY_PASS_DATA => [
                'audience_id' => $mirr->getSystemAudienceId(),
                'audience'    => [
                    'id' => $data['audience_id'],
                ],
                'type'        => AdTypeEnum::FB,
            ],
            'create'                  => [],
            'delete'                  => [],
        ];
        $body[$key] = $emls;

        $req = InnerRequestUtils::getRequest($systemInstall, $body);
        $this->runner->runTopologies(TopologyNameUtils::UPDATE_AUDIENCE, $systemInstall, $this, $req);
    }

}