<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Nette\Utils\Json;

/**
 * Class FacebookaudienceCreateAudienceConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceCreateAudienceConnector extends FacebookaudienceConnectorAbstract
{

    private const URL = '%s/%s/customaudiences?access_token=%s';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-create-audience-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists('data', $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field "data"',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $audienceId    = $systemInstall->getSettings()[FacebookaudienceSystem::CUSTOM_AUDIENCE] ?? '';
        $newList       = $systemInstall->getSettings()[FacebookaudienceSystem::NEW_LIST] ?? '';

        if (empty($audienceId)) {
            throw new CleverConnectorsException(
                'Missing Audience ID',
                CleverConnectorsException::MISSING_DATA
            );
        }

        if ($audienceId == FacebookaudienceSystem::CREATE_NEW && empty($newList)) {
            throw new CleverConnectorsException(
                'Missing Audience Name',
                CleverConnectorsException::MISSING_DATA
            );
        }

        if ($audienceId == FacebookaudienceSystem::CREATE_NEW &&
            !empty($newList) &&
            !$this->listExists($newList, $data['data'])
        ) {
            return $this->createNew($systemInstall, $dto, $newList);
        }

        return $dto;
    }

    /**
     * @param string $list
     * @param array  $data
     *
     * @return bool
     */
    private function listExists(string $list, array $data): bool
    {
        if (!empty($data)) {
            foreach ($data as $item) {
                if ($item['name'] == $list) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     * @param string        $newList
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    private function createNew(SystemInstall $systemInstall, ProcessDto $dto, string $newList): ProcessDto
    {
        $dto->setData(Json::encode([
            'name'    => $newList,
            'subtype' => 'CUSTOM',
        ]));

        $token       = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $adAccountId = $systemInstall->getSettings()[FacebookaudienceSystem::AD_ACCOUNT] ?? '';

        if (empty($adAccountId)) {
            throw new CleverConnectorsException(
                'Missing Ad Account ID',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $requestDto
            ->setUri(new Uri(sprintf(self::URL, $requestDto->getUri(), $adAccountId, $token)))
            ->setBody($dto->getData())
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        try {
            $response = $this->manager->send($requestDto);
        } catch (CurlException $e) {
            $this->logCurlException($e, $systemInstall);
            throw $e;
        }

        $this->saveAudience($response, $systemInstall);

        return $dto->setData($response->getBody());
    }

    /**
     * @param ResponseDto   $response
     * @param SystemInstall $systemInstall
     *
     * @throws CleverConnectorsException
     */
    private function saveAudience(ResponseDto $response, SystemInstall $systemInstall): void
    {
        $resData = Json::decode($response->getBody(), TRUE);
        if (!array_key_exists('id', $resData)) {
            throw new CleverConnectorsException(
                'Request to create new audience failed.',
                CleverConnectorsException::REQUEST_FAILED
            );
        }

        $this->system->setSettings($systemInstall, [
            FacebookaudienceSystem::CUSTOM_AUDIENCE => $resData['id'],
        ]);
        $this->dm->flush();
    }

}