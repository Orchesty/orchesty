<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/5/17
 * Time: 3:52 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class FacebookGetLeadformConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetLeadformConnector implements ConnectorInterface
{

    /**
     * @var CurlManager
     */
    private $curlManager;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var FacebookLeadsSystem
     */
    private $system;

    /** @var SystemInstallRepository|ObjectRepository */
    private $systemInstallRepository;

    /**
     * FacebookGetLeadformConnector constructor.
     *
     * @param FacebookLeadsSystem $system
     * @param DocumentManager     $dm
     * @param CurlManager         $curlManager
     */
    public function __construct(FacebookLeadsSystem $system, DocumentManager $dm, CurlManager $curlManager)
    {

        $this->curlManager             = $curlManager;
        $this->dm                      = $dm;
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebook-get-leadform-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Facebook Leads has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $response      = $this->makeRequest($systemInstall, $dto);

        return $dto->setData($response->getBody());
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @internal param string $pageId
     *
     */
    public function getLeadForms(SystemInstall $systemInstall, array $data): array
    {
        if (!array_key_exists(FacebookLeadsSystem::PAGE_ID, $data) ||
            empty($data[FacebookLeadsSystem::PAGE_ID])) {

            throw new CleverConnectorsException(
                'Missing key "page_id" in data',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $this->system->setSettings($systemInstall, [
            FacebookLeadsSystem::PAGE_ID => $data[FacebookLeadsSystem::PAGE_ID],
        ]);
        $this->dm->flush();

        $settings = $systemInstall->getSettings();

        $response = $this->makeRequest($systemInstall);
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $data = json_decode($response->getBody(), TRUE);

            $sForms = [];

            if (array_key_exists(SystemInstall::FORMS, $settings)) {
                $sForms = $settings[SystemInstall::FORMS];

                foreach ($sForms as $form) {
                    $this->removeForm($form, $form[FacebookLeadsSystem::FORM_ID]);
                }
            }

            foreach ($data['data'] as $form) {
                $sForms[] = [
                    FacebookLeadsSystem::FORM_ID   => $form['id'],
                    FacebookLeadsSystem::FORM_NAME => $form['name'],
                    FacebookLeadsSystem::FORM_LIST => NULL,
                ];
            }

            $sett[SystemInstall::FORMS] = $sForms;
            $systemInstall->setSettings($sett);
            $this->dm->flush();

            return $sForms;

        } else {
            throw new CleverConnectorsException(
                'Facebook Leads Error: Getting leads form failed.',
                CleverConnectorsException::REQUEST_FAILED
            );
        }
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $pageId
     *
     * @return string
     * @throws CleverConnectorsException
     * @internal param array $data
     *
     */
    private function getPageAccessToken(SystemInstall $systemInstall, string $pageId): string
    {
        $settings   = $systemInstall->getSettings();
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET); //
        $url        = new Uri(
            $requestDto->getUri(TRUE) . '/' . $pageId . '?fields=access_token&access_token=' . urlencode($settings[OAuth2Provider::ACCESS_TOKEN])
        );
        $response   = $this->curlManager->send(RequestDto::from($requestDto, $url));
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $data = json_decode($response->getBody(), TRUE);

            return $data['access_token'];
        } else {
            throw new CleverConnectorsException(
                'Facebook Leads Error: Getting leads page access token failed.',
                CleverConnectorsException::REQUEST_FAILED
            );
        }
    }

    /**
     * @param array      $array
     * @param int|string $id
     */
    private function removeForm(array &$array, $id): void
    {
        foreach ($array as $index => $item) {
            if ($id == $item['id']) {
                unset($array[$index]);
                break;
            }
        }
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param ProcessDto|null $dto
     *
     * @return ResponseDto
     * @throws CleverConnectorsException
     */
    private function makeRequest(SystemInstall $systemInstall, ?ProcessDto $dto = NULL): ResponseDto
    {
        $settings        = $systemInstall->getSettings();
        $pageId          = $settings[FacebookLeadsSystem::PAGE_ID];
        $pageAccessToken = $this->getPageAccessToken($systemInstall, $pageId);

        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $url        = new Uri(
            $requestDto->getUri(TRUE) . '/' . $pageId . '/leadgen_forms?limit=1000&fields=id%2Cname&access_token=' . urlencode($pageAccessToken)
        );
        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        return $this->curlManager->send(RequestDto::from($requestDto, $url));
    }

}