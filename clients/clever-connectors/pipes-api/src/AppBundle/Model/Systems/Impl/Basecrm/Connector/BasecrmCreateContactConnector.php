<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;

/**
 * Class BasecrmCreateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
class BasecrmCreateContactConnector extends BasecrmUpdateContactConnectorAbstract
{

    private const SUB_URL = '/v2/contacts';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'basecrm-create-contact-connector';
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
        $requestDto    = $this->system->getRequestDtoNonSync($systemInstall, CurlManager::METHOD_POST);
        $uri           = new Uri(rtrim($requestDto->getUri(TRUE), '/') . self::SUB_URL);

        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setUri($uri)
            ->setBody($dto->getData());

        try {
            $res = $this->curl->send($requestDto);
            $dto->setData($res->getBody());
        } catch (CurlException $e) {
            $this->connectorError($e, $this->system, $systemInstall, $dto);
        }

        return $dto;
    }

}