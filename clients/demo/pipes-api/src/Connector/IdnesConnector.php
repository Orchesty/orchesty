<?php declare(strict_types=1);

namespace Demo\Connector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;

/**
 * Class IdnesConnector
 *
 * @package Demo\Connector
 */
final class IdnesConnector extends ConnectorAbstract
{

    public const NAME = 'idnes-connector';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $requestDto = new RequestDto(new Uri('https://www.idnes.cz/'), 'GET', $dto);

        $this->getSender()->send($requestDto);

        return $dto;
    }

}
