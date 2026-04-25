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
        $entries = $this->fetchEntries($correlationIds);

        return array_map(static fn(array $e): string => $e[1], $entries);
    }

    /**
     * Returns audit checkpoint log entries scoped to the given correlationIds.
     *
     * The bridge-side audit emitter writes a structured `auditCheckpoint`
     * object into each log line:
     *   {
     *     "auditCheckpoint": {
     *       "role": "process_entry" | "process_step" | "process_exit",
     *       "payload": {...},            // optional — absent for marker-only nodes
     *       "resultCode": 0,             // SDK ResultCode the connector returned
     *       "resultStatus": "success",   // success|failed|repeat|trashed|limit|unknown
     *       "resultMessage": "...",      // truncated to 512 chars
     *       "httpStatus": 200            // HTTP status of bridge -> worker call
     *     },
     *     "correlationId": "...",
     *     "topologyId": "...", "topologyName": "...",
     *     "nodeId": "...", "nodeName": "..."
     *   }
     *
     * Filtering by per-entity audit data IDs is intentionally NOT done here:
     * upstream Mongo (`audit_entity` -> `audit_data` -> `topology_progress`)
     * already returned only the relevant correlationIds, and the bridge does
     * NOT include `auditEntityIds` in the log line (chunked batches would
     * otherwise carry 10k+ IDs per log entry). Multi-entity correlations are
     * surfaced grouped by `nodeName` in the UI.
     *
     * Output shape per entry:
     *   [
     *     'role'          => 'process_entry'|'process_step'|'process_exit',
     *     'payload'       => mixed,         // may be null for marker-only nodes
     *     'resultCode'    => int|null,
     *     'resultStatus'  => string|null,   // success|failed|repeat|trashed|limit|unknown
     *     'resultMessage' => string|null,
     *     'httpStatus'    => int|null,
     *     'time'          => '<unix-nano-timestamp>',
     *     'correlationId' => '...',
     *     'topologyId'    => '...',
     *     'topologyName'  => '...',
     *     'nodeName'      => '...',
     *   ]
     *
     * @param string[] $correlationIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function queryAuditCheckpointsByCorrelationIds(array $correlationIds): array
    {
        if ($correlationIds === []) {
            return [];
        }

        $entries = $this->fetchEntries($correlationIds);
        $result  = [];

        foreach ($entries as [$time, $line]) {
            $decoded = json_decode($line, TRUE);

            if (!is_array($decoded)) {
                continue;
            }

            $cp = $decoded['auditCheckpoint'] ?? NULL;

            if (
                !is_array($cp)
                || !in_array($cp['role'] ?? '', ['process_entry', 'process_step', 'process_exit'], TRUE)
            ) {
                continue;
            }

            $result[] = [
                'role'          => $cp['role'],
                'payload'       => $cp['payload'] ?? NULL, // may be missing for fields:[] marker checkpoints
                'resultCode'    => isset($cp['resultCode']) ? (int) $cp['resultCode'] : NULL,
                'resultStatus'  => isset($cp['resultStatus']) ? (string) $cp['resultStatus'] : NULL,
                'resultMessage' => isset($cp['resultMessage']) ? (string) $cp['resultMessage'] : NULL,
                'httpStatus'    => isset($cp['httpStatus']) ? (int) $cp['httpStatus'] : NULL,
                'time'          => $time,
                'correlationId' => $decoded['correlationId'] ?? NULL,
                'topologyId'    => $decoded['topologyId'] ?? NULL,
                'topologyName'  => $decoded['topologyName'] ?? NULL,
                'nodeName'      => $decoded['nodeName'] ?? NULL,
            ];
        }

        return $result;
    }

    /**
     * @param string[] $correlationIds
     *
     * @return array<int, array{string, string}>
     */
    private function fetchEntries(array $correlationIds): array
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

        return $allEntries;
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
