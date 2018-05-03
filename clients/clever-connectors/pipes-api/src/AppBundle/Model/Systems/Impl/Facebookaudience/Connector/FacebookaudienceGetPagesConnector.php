<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookaudienceGetPagesConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceGetPagesConnector extends FacebookaudienceConnectorAbstract
{

    use FacebookPaginatorTrait;

    private const URL = '%s/me/accounts?type=page&access_token=%s';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-get-pages-connector';
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
        $res           = [];
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req           = $this->prepareRequestDto($systemInstall, $dto);

        try {
            $res = $this->loopThroughPages($req);
        } catch (CurlException $e) {
            return $this->logConnectorError($e, $systemInstall, $this->system, $dto);
        }

        return $dto->setData(json_encode($res));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CurlException
     * @throws SystemException
     */
    public function getPages(SystemInstall $systemInstall): array
    {
        $res = [];
        $req = $this->prepareRequestDto($systemInstall);

        try {
            $res = $this->loopThroughPages($req);
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