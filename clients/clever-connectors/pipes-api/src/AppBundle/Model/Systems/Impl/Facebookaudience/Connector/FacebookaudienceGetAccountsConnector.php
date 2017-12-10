<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

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
     *
     * @return array
     * @throws SystemException
     */
    public function getAccounts(SystemInstall $systemInstall): array
    {
        $res      = [];
        $response = $this->makeRequest($systemInstall);

        if ($response->getStatusCode() == 200) {
            $data = json_decode($response->getBody(), TRUE);
            if (array_key_exists('data', $data) && is_array($data) && !empty($data)) {
                foreach ($data['data'] as $item) {
                    $res[] = $item;
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