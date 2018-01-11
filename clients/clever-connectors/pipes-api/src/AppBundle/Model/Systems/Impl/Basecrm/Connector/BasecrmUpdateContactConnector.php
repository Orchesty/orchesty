<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;

/**
 * Class BasecrmUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
class BasecrmUpdateContactConnector extends BasecrmUpdateContactConnectorAbstract
{

    private const SUB_URL = '/v2/contacts/%s';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'basecrm-update-contact-connector';
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
        $requestDto    = $this->system->getRequestDtoNonSync($systemInstall, CurlManager::METHOD_PUT);

        $data = json_decode($dto->getData(), TRUE);

        $uri = new Uri(sprintf(
                rtrim($requestDto->getUri(TRUE), '/') . self::SUB_URL,
                $data['id']
            )
        );

        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setUri($uri)
            ->setBody($data['body']);

        $res = NULL;
        try {
            $res = $this->curl->send($requestDto);
        } catch (CurlException $e) {
            if ($e->getResponse()) {
                $this->logError($e->getResponse()->getStatusCode(), $this->system, $systemInstall);

                if ($e->getResponse()->getStatusCode() === 404) {
                    throw new CleverConnectorsException(
                        sprintf('Contact with id [%s] wasn\'t find, BaseCRM updateContactConnector.', $data['id']),
                        CleverConnectorsException::REQUEST_FAILED);
                } else {
                    throw new CleverConnectorsException(
                        sprintf('Failed to update, BaseCRM updateContactConnector, %s', $e->getResponse()->getBody()),
                        CleverConnectorsException::REQUEST_FAILED);
                }
            }

            throw $e;
        }

        $body = json_decode($res->getBody(), TRUE);
        $key  = CleverCustomKeysEnum::getFromType(CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders()) ?? '');

        if (!is_array($body)
            || !array_key_exists('data', $body)
            || !array_key_exists('custom_fields', $body['data'])
        ) {
            throw new CleverConnectorsException(
                'Malformed response data, BaseCRM updateContactConnector.',
                CleverConnectorsException::MISSING_DATA);
        } else if (!array_key_exists($key, $body['data']['custom_fields'])) {
            throw new CleverConnectorsException(
                sprintf('Requested field [%s] doesn\'t exist, BaseCRM updateContactConnector.', $key),
                CleverConnectorsException::REQUEST_FAILED);
        }

        return $dto->setData($res->getBody());
    }

}