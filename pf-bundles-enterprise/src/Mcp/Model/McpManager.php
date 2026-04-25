<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyProgressRepository;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditData;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditDataField;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditDataRepository;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditEntityRepository;

/**
 * Class McpManager
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class McpManager
{

    /**
     * Locale-en collation with strength 2 ignores case and diacritics.
     * Used so MCP lookups (`sku-002` vs `SKU-002`, `product` vs `Product`)
     * match the same row. Indexes on AuditEntity.key and AuditData.fields
     * carry the same collation so these queries stay index-served.
     */
    private const array CASE_INSENSITIVE_COLLATION = ['locale' => 'en', 'strength' => 2];

    /**
     * @var ObjectRepository<AuditEntity>&AuditEntityRepository
     */
    private ObjectRepository $auditEntityRepository;

    /**
     * @var ObjectRepository<AuditData>&AuditDataRepository
     */
    private ObjectRepository $auditDataRepository;

    /**
     * @var ObjectRepository<TopologyProgress>&TopologyProgressRepository
     */
    private ObjectRepository $topologyProgressRepository;

    /**
     * McpManager constructor.
     *
     * @param DocumentManager $dm
     * @param LokiManager     $lokiManager
     */
    public function __construct(DocumentManager $dm, private readonly LokiManager $lokiManager)
    {
        $this->auditEntityRepository      = $dm->getRepository(AuditEntity::class);
        $this->auditDataRepository        = $dm->getRepository(AuditData::class);
        $this->topologyProgressRepository = $dm->getRepository(TopologyProgress::class);
    }

    /**
     * @return mixed[]
     */
    public function getTopologiesEntitiesManifest(): array
    {
        /** @var AuditEntity[] $entities */
        $entities = $this->auditEntityRepository->findBy([]);

        return array_map(static function (AuditEntity $entity): array {
            $properties = [];

            foreach ($entity->getFields() as $field) {
                $properties[$field->getKey()] = [
                    'description' => $field->getName(),
                    'type' => 'string',
                ];
            }

            $entityName = $entity->getName();

            return [
                'description'   => sprintf(
                    'Returns the audit history of a single %s entity: for every '
                    . 'topology run that touched it, an `entry` snapshot (when '
                    . 'business data entered the process), zero or more `steps` '
                    . '(intermediate audit checkpoints, possibly from sibling '
                    . 'entities of the same correlation), and an `exit` snapshot '
                    . '(when the process delegated the data to its destination). '
                    . 'Each snapshot carries a `time`, `nodeName` and the picked '
                    . '`payload` (subset of fields declared in the audit node\'s '
                    . 'allowlist). Snapshots are null when the topology has no '
                    . 'AuditCheckpointNode with the corresponding role.',
                    $entityName,
                ),
                'id'            => $entity->getKey(),
                'input_schema'  => [
                    'minProperties' => 1,
                    'properties'    => $properties,
                    'type'          => 'object',
                ],
                'kind'          => 'query',
                'output_schema' => [
                    'properties' => [
                        'entity'      => ['type' => 'string'],
                        'identifier'  => ['type' => 'object'],
                        'identifiers' => [
                            'description' => 'Union of every identifier (key=>value) registered for the matched audit-data rows. Carries the full identifier set known for the entity instance(s); empty object when no audit-data row matches the query.',
                            'type'        => 'object',
                        ],
                        'runs'        => [
                            'items' => [
                                'properties' => [
                                    'correlationId' => ['type' => 'string'],
                                    'entry'         => [
                                        'description' => 'Snapshot at process entry (role=process_entry) or null if no such checkpoint.',
                                        'type'        => ['object', 'null'],
                                    ],
                                    'exit'          => [
                                        'description' => 'Snapshot at process exit (role=process_exit) or null if no such checkpoint.',
                                        'type'        => ['object', 'null'],
                                    ],
                                    'steps'         => [
                                        'description' => 'Intermediate snapshots (role=process_step) in chronological order.',
                                        'items'       => ['type' => 'object'],
                                        'type'        => 'array',
                                    ],
                                    'topologyId'    => ['type' => ['string', 'null']],
                                    'topologyName'  => ['type' => ['string', 'null']],
                                ],
                                'type'       => 'object',
                            ],
                            'type'  => 'array',
                        ],
                    ],
                    'type'       => 'object',
                ],
                'title'         => sprintf('%s history', $entityName),
            ];
        }, $entities);
    }

    /**
     * @return mixed[]
     */
    public function getManifest(): array
    {
        return $this->getTopologiesEntitiesManifest();
    }

    /**
     * Per-entity Trace lookup. Returns entry/steps/exit snapshots for each
     * topology run that touched the requested entity.
     *
     * Response shape:
     *   {
     *     entity:     'order',
     *     identifier: { id: 'ord-017' },
     *     runs: [
     *       {
     *         correlationId: '...',
     *         topologyId:    '...',
     *         topologyName:  '...',
     *         entry: { time, nodeName, payload, resultCode, resultStatus, resultMessage, httpStatus } | null,
     *         steps: [ { time, nodeName, payload, resultCode, resultStatus, resultMessage, httpStatus }, ... ],
     *         exit:  { time, nodeName, payload, resultCode, resultStatus, resultMessage, httpStatus } | null
     *       }
     *     ]
     *   }
     *
     * Runs without any audit checkpoint log are still returned with `null`
     * entry/exit and an empty `steps` array so the UI can render "Not
     * captured" on non-instrumented topologies.
     *
     * Multi-entity correlations: if a single run audits multiple entities
     * (e.g. an Order plus its LineItems), the returned `steps` may include
     * checkpoints from sibling entities of the same correlationId. The UI
     * groups them by `nodeName` so the user can keep context.
     *
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function run(array $data): array
    {
        $audit      = $data['audit'];
        $searchData = $data['data'];

        $emptyResponse = [
            'entity'      => $audit,
            'identifier'  => $searchData,
            'identifiers' => [],
            'runs'        => [],
        ];

        // Lookup `audit` (entity key) and `searchData` (identifier key/value)
        // case-insensitively. Mongo collation `strength: 2` ignores case +
        // diacritics; matching collations on the indexes (see AuditEntity /
        // AuditData annotations) keep these queries index-served.
        //
        // Doctrine ODM Query\Builder doesn't expose a `collation()` setter,
        // but `getQuery($options)` passes the options array straight into the
        // MongoDB driver call (see Doctrine\ODM\MongoDB\Query\Query::runQuery)
        // — so we forward `collation` there.
        $collationOptions = ['collation' => self::CASE_INSENSITIVE_COLLATION];

        /** @var AuditEntity|null $auditEntity */
        $auditEntity = $this
            ->auditEntityRepository
            ->createQueryBuilder()
            ->field(AuditEntity::KEY)
            ->equals($audit)
            ->getQuery($collationOptions)
            ->getSingleResult(); /** @phpstan-ignore-line */

        if (!$auditEntity) {
            return $emptyResponse;
        }

        $queryBuilder = $this
            ->auditDataRepository
            ->createQueryBuilder()
            ->field(AuditData::ENTITY)
            ->equals($auditEntity->getId());

        foreach ($searchData as $key => $value) {
            $queryBuilder
                ->field(AuditData::FIELDS)
                ->elemMatch(
                    $queryBuilder
                        ->expr()
                        ->field(AuditDataField::KEY)
                        ->equals($key)
                        ->field(AuditDataField::VALUE)
                        ->equals($value),
                );
        }

        /** @var AuditData[] $auditDataDocs */
        $auditDataDocs = $queryBuilder->getQuery($collationOptions)->execute()->toArray(); /** @phpstan-ignore-line */

        $auditDataIds = array_map(
            static fn(AuditData $auditData): string => $auditData->getId(),
            $auditDataDocs,
        );

        if ($auditDataIds === []) {
            return $emptyResponse;
        }

        // Union of every identifier registered for the matched audit-data
        // rows. This is the same "pairing table" used to resolve correlation
        // IDs, so it carries the full identifier set known for the entity
        // instance(s) — surfaced to the UI for the report header.
        $identifiers = $this->collectIdentifiers($auditDataDocs);

        $progresses = $this->topologyProgressRepository->findBy(['auditData' => ['$in' => $auditDataIds]]);

        $correlationIds = array_map(
            static fn(TopologyProgress $progress): string => $progress->getId(),
            $progresses,
        );

        if ($correlationIds === []) {
            return [
                'entity'      => $audit,
                'identifier'  => $searchData,
                'identifiers' => $identifiers,
                'runs'        => [],
            ];
        }

        $logs = $this->lokiManager->queryAuditCheckpointsByCorrelationIds($correlationIds);

        return [
            'entity'      => $audit,
            'identifier'  => $searchData,
            'identifiers' => $identifiers,
            'runs'        => $this->groupLogsIntoRuns($logs, $progresses),
        ];
    }

    /**
     * Collects a deduplicated key=>value identifier set from the matched
     * AuditData documents. First value per key wins.
     *
     * @param AuditData[] $auditDataDocs
     *
     * @return array<string, string>
     */
    private function collectIdentifiers(array $auditDataDocs): array
    {
        $identifiers = [];

        foreach ($auditDataDocs as $auditData) {
            foreach ($auditData->getFields() as $field) {
                $key = $field->getKey();
                if (!isset($identifiers[$key])) {
                    $identifiers[$key] = $field->getValue();
                }
            }
        }

        return $identifiers;
    }

    /**
     * Groups checkpoint logs by `correlationId` into runs of the form
     * `{ entry, steps[], exit }`.
     *
     *  - First `process_entry` log per run wins (entry is by definition
     *    a single point).
     *  - Last `process_exit` log per run wins (so retries override earlier
     *    failed attempts).
     *  - All `process_step` logs are appended in chronological order. Note
     *    that for multi-entity correlations these may come from sibling
     *    entities sharing the same correlationId — the UI groups them by
     *    `nodeName`.
     *
     * Topology progresses are pre-seeded so runs without any matching log
     * still appear (with null entry/exit and empty steps) — the UI renders
     * this as "Not captured", helping spot non-instrumented topologies.
     *
     * @param array<int, array<string, mixed>> $logs
     * @param TopologyProgress[]               $progresses
     *
     * @return array<int, array<string, mixed>>
     */
    private function groupLogsIntoRuns(array $logs, array $progresses): array
    {
        $runs = [];

        foreach ($progresses as $progress) {
            $runs[$progress->getId()] = [
                'correlationId' => $progress->getId(),
                'entry'         => NULL,
                'exit'          => NULL,
                'steps'         => [],
                'topologyId'    => $progress->getTopologyId(),
                'topologyName'  => NULL,
            ];
        }

        foreach ($logs as $log) {
            $cid = $log['correlationId'] ?? '';

            if ($cid === '') {
                continue;
            }

            if (!isset($runs[$cid])) {
                $runs[$cid] = [
                    'correlationId' => $cid,
                    'entry'         => NULL,
                    'exit'          => NULL,
                    'steps'         => [],
                    'topologyId'    => $log['topologyId'] ?? NULL,
                    'topologyName'  => $log['topologyName'] ?? NULL,
                ];
            } else {
                if ($runs[$cid]['topologyId'] === NULL && isset($log['topologyId'])) {
                    $runs[$cid]['topologyId'] = $log['topologyId'];
                }

                if ($runs[$cid]['topologyName'] === NULL && isset($log['topologyName'])) {
                    $runs[$cid]['topologyName'] = $log['topologyName'];
                }
            }

            $snapshot = [
                'httpStatus'    => $log['httpStatus'] ?? NULL,
                'nodeName'      => $log['nodeName'] ?? NULL,
                'payload'       => $log['payload'] ?? NULL,
                'resultCode'    => $log['resultCode'] ?? NULL,
                'resultMessage' => $log['resultMessage'] ?? NULL,
                'resultStatus'  => $log['resultStatus'] ?? NULL,
                'time'          => $log['time'] ?? NULL,
            ];

            switch ($log['role']) {
                case 'process_entry':
                    if ($runs[$cid]['entry'] === NULL) {
                        $runs[$cid]['entry'] = $snapshot;
                    }
                    break;
                case 'process_exit':
                    $runs[$cid]['exit'] = $snapshot;
                    break;
                case 'process_step':
                    $runs[$cid]['steps'][] = $snapshot;
                    break;
            }
        }

        return array_values($runs);
    }

}
