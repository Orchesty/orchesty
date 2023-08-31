<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Cron;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\Utils\String\Json;
use Throwable;

/**
 * Class CronManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Cron
 */
final class CronManager
{

    private const URL = '%s/crons';

    private const TOPOLOGY   = 'topology';
    private const NODE       = 'node';
    private const TIME       = 'time';
    private const PARAMETERS = 'parameters';

    /**
     * CronManager constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param string               $cronHost
     */
    public function __construct(private CurlManagerInterface $curlManager, private readonly string $cronHost)
    {
    }

    /**
     * @return ResponseDto
     * @throws CurlException
     * @throws CronException
     */
    public function getAll(): ResponseDto
    {
        return $this->sendAndProcessRequest(
            new RequestDto($this->getUrl(self::URL), CurlManager::METHOD_GET, new ProcessDto()),
        );
    }

    /**
     * @param Node $node
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function upsert(Node $node): ResponseDto
    {
        return $this->batchUpsert([$node]);
    }

    /**
     * @param Node $node
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function delete(Node $node): ResponseDto
    {
        $body = [
            [
                self::TOPOLOGY => $node->getTopology(),
                self::NODE     => $node->getId(),
            ],
        ];

        $dto = (new RequestDto(
            $this->getUrl(self::URL),
            CurlManager::METHOD_DELETE,
            new ProcessDto(),
        ))->setBody(Json::encode($body));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node[] $nodes
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function batchUpsert(array $nodes): ResponseDto
    {
        $body = array_values(
            array_map(static fn(Node $node): array => [
                self::TOPOLOGY   => $node->getTopology(),
                self::NODE       => $node->getId(),
                self::TIME       => $node->getCron(),
                self::PARAMETERS => $node->getCronParams(),
            ], $nodes),
        );

        $dto = (new RequestDto(
            $this->getUrl(self::URL),
            CurlManager::METHOD_PATCH,
            new ProcessDto(),
        ))->setBody(Json::encode($body));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param string $url
     *
     * @return Uri
     */
    private function getUrl(string $url): Uri
    {
        return new Uri(sprintf($url, rtrim($this->cronHost, '/')));
    }

    /**
     * @param RequestDto $dto
     *
     * @return ResponseDto
     * @throws CronException
     */
    private function sendAndProcessRequest(RequestDto $dto): ResponseDto
    {
        $dto->setHeaders(
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        );

        try {
            return $this->curlManager->send($dto, [RequestOptions::HTTP_ERRORS => TRUE]);
        } catch (Throwable $e) {
            throw new CronException(sprintf('Cron API failed: %s', $e->getMessage()), CronException::CRON_EXCEPTION);
        }
    }

}
