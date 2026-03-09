<?php declare(strict_types=1);

namespace Demo\JsonPlaceholder\Connector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class GetPost
 *
 * @package Demo\JsonPlaceholder\Connector
 */
final class GetPost extends ConnectorAbstract
{

    public const string NAME = 'get-post';

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
     * @throws ConnectorException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        return $dto->setJsonData(
            $this->getSender()->send(new RequestDto(
                new Uri(sprintf('https://jsonplaceholder.typicode.com/posts/%s', $dto->getJsonData()['id'])),
                'GET',
                $dto,
            ))->getJsonBody(),
        )->addAuditHeader('post', 'id', [['id' => (string) $dto->getJsonData()['id']]]);
    }

}
