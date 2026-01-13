<?php declare(strict_types=1);

namespace Demo\Connector\HttpStatus;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class HttpStatus404Connector
 *
 * @package Demo\Connector\HttpStatus
 */
final class HttpStatus404Connector extends ConnectorAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'http-status-404-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     * @throws ConnectorException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $response = $this->getSender()->send(
            new RequestDto(new Uri('https://mock.httpstatus.io/404'), CurlManager::METHOD_GET, $dto),
        );

        return $dto->setStopProcess(
            ProcessDto::STOP_AND_FAILED,
            sprintf('status: %s, body: %s', $response->getStatusCode(), $response->getBody()),
        );
    }

}
