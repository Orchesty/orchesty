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
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookGetLeadformConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetLeadformConnector extends FacebookLeadConnectorAbstract
{

    /**
     * @var CurlManager
     */
    private $curlManager;

    /**
     * FacebookGetLeadformConnector constructor.
     *
     * @param FacebookLeadsSystem $system
     * @param DocumentManager     $dm
     * @param CurlManager         $curlManager
     */
    public function __construct(
        FacebookLeadsSystem $system,
        DocumentManager $dm,
        CurlManager $curlManager
    )
    {
        parent::__construct($system, $dm);

        $this->curlManager = $curlManager;
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
     * @return ProcessDto
     * @throws CurlException
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->prepareRequestDto($systemInstall, $dto);

        try {
            $response = $this->curlManager->send($requestDto);
        } catch (CurlException $e) {
            return $this->logConnectorError($e, $systemInstall, $this->system, $dto);
        }

        return $dto->setData($response->getBody());
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    public function getLeadForms(SystemInstall $systemInstall, array $data): array
    {
        if (!array_key_exists(FacebookLeadsSystem::PAGE_ID, $data) ||
            empty($data[FacebookLeadsSystem::PAGE_ID])
        ) {

            throw new CleverConnectorsException(
                'Missing key "page_id" in data',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $this->system->setSettings($systemInstall, [
            FacebookLeadsSystem::PAGE_ID => $data[FacebookLeadsSystem::PAGE_ID],
        ]);
        $this->dm->flush();

        $settings   = $systemInstall->getSettings();
        $requestDto = $this->prepareRequestDto($systemInstall);

        try {
            $response = $this->curlManager->send($requestDto);
        } catch (CurlException $e) {
            $this->logConnectorError($e, $systemInstall, $this->system);
        }

        if (isset($response) && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $data = json_decode($response->getBody(), TRUE);

            $sForms = [];

            if (array_key_exists(SystemInstall::FORMS, $settings)) {
                $sForms = $settings[SystemInstall::FORMS];

                foreach ($sForms as $index => $form) {
                    if (!$this->removeForm($data['data'], $form[FacebookLeadsSystem::FORM_ID])) {
                        unset($sForms[$index]);
                    }
                }
            }

            foreach ($data['data'] as $form) {
                $sForms[] = [
                    FacebookLeadsSystem::FORM_ID   => $form['id'],
                    FacebookLeadsSystem::FORM_NAME => $form['name'],
                    FacebookLeadsSystem::FORM_LIST => NULL,
                ];
            }

            $sForms = array_values($sForms);

            $settings[SystemInstall::FORMS] = $sForms;
            $systemInstall->setSettings($settings);
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
     * @throws CurlException
     */
    private function getPageAccessToken(SystemInstall $systemInstall, string $pageId): string
    {
        $settings   = $systemInstall->getSettings();
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET); //
        $url        = new Uri(
            $requestDto->getUri(TRUE) . '/' . $pageId . '?fields=access_token&access_token=' . urlencode($settings[OAuth2Provider::ACCESS_TOKEN])
        );

        try {
            $response = $this->curlManager->send(RequestDto::from($requestDto, $url));
        } catch (CurlException $e) {
            $this->logConnectorError($e, $systemInstall, $this->system);
        }

        if (isset($response) && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
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
     *
     * @return bool
     */
    private function removeForm(array &$array, $id): bool
    {
        foreach ($array as $index => $item) {
            if ($id == $item['id']) {
                unset($array[$index]);

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param ProcessDto|null $dto
     *
     * @return RequestDto
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    private function prepareRequestDto(SystemInstall $systemInstall, ?ProcessDto $dto = NULL): RequestDto
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

        return RequestDto::from($requestDto, $url);
    }

}