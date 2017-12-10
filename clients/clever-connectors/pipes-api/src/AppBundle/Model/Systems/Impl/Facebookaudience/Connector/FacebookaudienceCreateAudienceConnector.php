<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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

    private const URL = '%s/act_%s/customaudiences?fields=name&access_token=%s';

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

        if (!is_array($data) || !array_key_exists('name', $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field "name"',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $adAccountId   = $systemInstall->getSettings()[FacebookaudienceSystem::AD_ACCOUNT_ID];
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $requestDto
            ->setUri(new Uri(sprintf(self::URL, $requestDto->getUri(), $adAccountId)))
            ->setBody($dto->getData())
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $response = $this->manager->send($requestDto);

        return $dto->setData($response->getBody());
    }

    /**
     * @return array
     */
    public function getAudiences(): array
    {

    }

    /**
     * @param SystemInstall   $systemInstall
     * @param ProcessDto|null $dto
     *
     * @return ResponseDto
     * @throws SystemException
     */
    private function makeRequest(SystemInstall $systemInstall, ?ProcessDto $dto = NULL): ResponseDto
    {
        $token      = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $url        = sprintf(self::URL, $requestDto->getUri(), $token);
        $requestDto->setUri(new Uri($url));

        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        return $this->manager->send($requestDto);
    }

}