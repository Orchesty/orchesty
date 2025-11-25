<?php declare(strict_types=1);

namespace Demo\JsonPlaceholder\Batch;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class ListPosts
 *
 * @package Demo\JsonPlaceholder\Batch
 */
final class ListPosts extends BatchAbstract
{

    public const string NAME = 'list-posts';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param BatchProcessDto $dto
     *
     * @return BatchProcessDto
     * @throws CurlException
     * @throws ConnectorException
     */
    public function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        $posts = $this->getSender()->send(new RequestDto(
            new Uri('https://jsonplaceholder.typicode.com/posts'),
            'GET',
            $dto,
        ))->getJsonBody();

        foreach ($posts as $post) {
            $dto->addItem($post, $dto->getUser());
        }

        return $dto;
    }

}
