<?php declare(strict_types=1);

namespace Demo\Connector\HttpStatus;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class HttpStatus200Connector
 *
 * @package Demo\Connector\HttpStatus
 */
final class HttpStatus200Connector extends ConnectorAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'http-status-200-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $this->getSender()->send(
            new RequestDto(new Uri('https://mock.httpstatus.io/200'), CurlManager::METHOD_GET, $dto),
        );

        return $dto;
    }

}
