<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Model;

use DateTimeImmutable;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use RuntimeException;

/**
 * Class LokiManager
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class LokiManager
{

    private const int LIMIT       = 1_000;
    private const int MAX_ENTRIES = 10_000;

    /**
     * LokiManager constructor.
     *
     * @param CurlManager $curlManager
     * @param string      $lokiUrl
     */
    public function __construct(private readonly CurlManager $curlManager, private readonly string $lokiUrl)
    {
    }

    /**
     * @param string[] $correlationIds
     *
     * @return string[]
     */
    public function queryByCorrelationIds(array $correlationIds): array
    {
        if ($this->lokiUrl === '' || $correlationIds === []) {
            return [];
        }

        $query      = sprintf('{correlationId=~"%s"}', implode('|', $correlationIds));
        $start      = (new DateTimeImmutable('-30 days'))->format('Y-m-d\TH:i:s\Z');
        $allEntries = [];

        do {
            $url = sprintf(
                '%s/loki/api/v1/query_range?%s',
                rtrim($this->lokiUrl, '/'),
                http_build_query([
                    'direction' => 'forward',
                    'limit'     => self::LIMIT,
                    'query'     => $query,
                    'start'     => $start,
                ]),
            );

            $dto = new RequestDto(
                new Uri($url),
                CurlManager::METHOD_GET,
                new ProcessDto(),
            );

            $response = $this->curlManager->send($dto);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException(
                    sprintf('Loki query failed with status %d', $response->getStatusCode()),
                );
            }

            $entries    = $this->collectEntries($response->getJsonBody());
            $allEntries = array_merge($allEntries, $entries);

            if ($entries === []) {
                break;
            }

            $maxTs = max(array_column($entries, 0));
            $start = (string) ((int) $maxTs + 1);
        } while (count($allEntries) < self::MAX_ENTRIES);

        usort($allEntries, static fn(array $a, array $b): int => $a[0] <=> $b[0]);

        return array_map(static fn(array $e): string => $e[1], $allEntries);
    }

    /**
     * @param mixed[] $body
     *
     * @return array<array{string, string}>
     */
    private function collectEntries(array $body): array
    {
        $entries = [];

        foreach ($body['data']['result'] ?? [] as $stream) {
            foreach ($stream['values'] ?? [] as $entry) {
                if (isset($entry[0], $entry[1]) && $entry[1] !== '') {
                    $entries[] = [$entry[0], $entry[1]];
                }
            }
        }

        return $entries;
    }

}
