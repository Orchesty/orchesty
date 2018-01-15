<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class FacebookaudienceGetAccountsConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceGetAccountsConnector extends FacebookaudienceConnectorAbstract
{

    private const URL = '%s/me/adaccounts?fields=name&access_token=%s';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-get-accounts-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->prepareRequestDto($systemInstall, $dto);

        try {
            $response = $this->manager->send($requestDto);
        } catch (CurlException $e) {
            return $this->logConnectorError($e, $systemInstall, $this->system, $dto);
        }

        return $dto->setData($response->getBody());
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CurlException
     * @throws SystemException
     */
    public function getAccounts(SystemInstall $systemInstall): array
    {
        $res        = [];
        $requestDto = $this->prepareRequestDto($systemInstall);

        try {
            $response = $this->manager->send($requestDto);
        } catch (CurlException $e) {
            $this->logConnectorError($e, $systemInstall, $this->system);
        }

        if (isset($response) && $response->getStatusCode() == 200) {
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
     * @return RequestDto
     * @throws SystemException
     */
    private function prepareRequestDto(SystemInstall $systemInstall, ?ProcessDto $dto = NULL): RequestDto
    {
        $token      = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $url        = sprintf(self::URL, $requestDto->getUri(), $token);
        $requestDto->setUri(new Uri($url));

        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        return $requestDto;
    }

}