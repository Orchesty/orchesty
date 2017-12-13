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
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class FacebookaudienceGetAudiencesConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceGetAudiencesConnector extends FacebookaudienceConnectorAbstract
{

    private const URL = '%s/%s/customaudiences?fields=name&access_token=%s';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-get-audiences-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws CleverConnectorsException
     * @throws SystemException
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
     * @throws SystemException
     */
    public function getAudiences(SystemInstall $systemInstall, array $data): array
    {
        if (!array_key_exists(FacebookaudienceSystem::AD_ACCOUNT, $data) ||
            empty($data[FacebookaudienceSystem::AD_ACCOUNT])) {

            throw new CleverConnectorsException(
                'Missing key "ad_account" in data',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $this->system->setSettings($systemInstall, [
            FacebookaudienceSystem::AD_ACCOUNT => $data[FacebookaudienceSystem::AD_ACCOUNT],
        ]);
        $this->dm->flush();

        $res                                     = [];
        $res[FacebookaudienceSystem::CREATE_NEW] = 'Create New';
        $response                                = $this->makeRequest($systemInstall, NULL);

        if ($response->getStatusCode() == 200) {
            $data = json_decode($response->getBody(), TRUE);
            if (array_key_exists('data', $data) && is_array($data) && !empty($data)) {
                foreach ($data['data'] as $item) {
                    $res[$item['id']] = $item['name'];
                }
            }
        }

        return $res;
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param ProcessDto|null $dto
     *
     * @return ResponseDto
     * @throws SystemException
     * @throws CleverConnectorsException
     */
    private function makeRequest(SystemInstall $systemInstall, ?ProcessDto $dto = NULL): ResponseDto
    {
        $adAccountId = $systemInstall->getSettings()[FacebookaudienceSystem::AD_ACCOUNT] ?? '';

        if (empty($adAccountId)) {
            throw new CleverConnectorsException(
                'Missing Ad Account ID',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $token       = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $requestDto  = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setUri(new Uri(sprintf(self::URL, $requestDto->getUri(), $adAccountId, $token)));

        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        return $this->manager->send($requestDto);
    }

}