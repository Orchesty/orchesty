<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookaudienceGetAudiencesConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceGetAudiencesConnector extends FacebookaudienceConnectorAbstract
{

    use FacebookPaginatorTrait;

    private const URL = '%s/act_%s/customaudiences?fields=name&access_token=%s';

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
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->prepareRequestDto($systemInstall, $dto);

        try {
            $res = $this->loopThroughPages($requestDto);
        } catch (CurlException $e) {
            return $this->logConnectorError($e, $systemInstall, $this->system, $dto);
        }

        return $dto->setData(json_encode($res));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function getAudiences(SystemInstall $systemInstall): array
    {
        $res        = [];
        $requestDto = $this->prepareRequestDto($systemInstall, NULL);

        try {
            $res = $this->loopThroughPages($requestDto);
        } catch (CurlException $e) {
            $this->logConnectorError($e, $systemInstall, $this->system);
        }

        return $res;
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param ProcessDto|null $dto
     *
     * @return RequestDto
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    private function prepareRequestDto(SystemInstall $systemInstall, ?ProcessDto $dto = NULL): RequestDto
    {
        $adAccountId = $systemInstall->getSettings()[FacebookaudienceSystem::AD_ACCOUNT] ?? '';

        if (empty($adAccountId)) {
            throw new CleverConnectorsException(
                'Missing Ad Account ID',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $token      = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $requestDto->setUri(new Uri(sprintf(self::URL, $requestDto->getUri(), $adAccountId, $token)));

        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        return $requestDto;
    }

}