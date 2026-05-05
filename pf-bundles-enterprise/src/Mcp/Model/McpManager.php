<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Model;

use DateTimeInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyProgressRepository;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditData;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditDataField;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditDataRepository;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditEntityRepository;
use LogicException;

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
     * @var ObjectRepository<Topology>&TopologyRepository
     */
    private ObjectRepository $topologyRepository;

    /**
     * McpManager constructor.
     *
     * @param DocumentManager      $dm
     * @param LokiManager          $lokiManager
     * @param MetricsAggregator    $metricsAggregator
     * @param DocsSearchClient     $docsSearchClient
     * @param DocsReadClient       $docsReadClient
     * @param OnboardingStepClient $onboardingStepClient
     */
    public function __construct(
        DocumentManager $dm,
        private readonly LokiManager $lokiManager,
        private readonly MetricsAggregator $metricsAggregator,
        private readonly DocsSearchClient $docsSearchClient,
        private readonly DocsReadClient $docsReadClient,
        private readonly OnboardingStepClient $onboardingStepClient,
    )
    {
        $this->auditEntityRepository      = $dm->getRepository(AuditEntity::class);
        $this->auditDataRepository        = $dm->getRepository(AuditData::class);
        $this->topologyProgressRepository = $dm->getRepository(TopologyProgress::class);
        $this->topologyRepository         = $dm->getRepository(Topology::class);
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
                    'type'        => 'string',
                ];
            }

            // Optional top-level date-range filters. The Trace LLM is taught
            // to put exactly one of these alongside `audit`/`data`. They are
            // forwarded to topology_progress.startedAt and Loki query window
            // by McpManager::run().
            $properties['day']    = [
                'description' => 'Restrict to a single calendar day (UTC), e.g. "2026-03-12".',
                'format'      => 'date',
                'type'        => 'string',
            ];
            $properties['from']   = [
                'description' => 'ISO 8601 start of the date range (inclusive). Use together with "to".',
                'format'      => 'date-time',
                'type'        => 'string',
            ];
            $properties['to']     = [
                'description' => 'ISO 8601 end of the date range (exclusive). Use together with "from".',
                'format'      => 'date-time',
                'type'        => 'string',
            ];
            $properties['period'] = [
                'description' => 'Named relative range: today, yesterday, this_week, last_7d, last_30d.',
                'enum'        => ['today', 'yesterday', 'this_week', 'last_7d', 'last_30d'],
                'type'        => 'string',
            ];

            $entityName = $entity->getName();

            return [
                'description'   => sprintf(
                    'Returns the audit history of a single %s entity: for every topology run that touched it, an `entry` snapshot (when business data entered the process), zero or more `steps` (intermediate audit checkpoints, possibly from sibling entities of the same correlation), and an `exit` snapshot (when the process delegated the data to its destination). Each snapshot carries a `time`, `nodeName` and the picked `payload` (subset of fields declared in the audit node\'s allowlist). Snapshots are null when the topology has no AuditCheckpointNode with the corresponding role. Optional top-level `day` / `from`+`to` / `period` narrow the result to topology runs started in that window.',
                    $entityName,
                ),
                'id'            => $entity->getKey(),
                'input_schema'  => [
                    'minProperties' => 1,
                    'properties'    => $properties,
                    'type'          => 'object',
                ],
                'kind'          => 'entity_history',
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
        return [
            ...$this->getTopologiesEntitiesManifest(),
            ...$this->getMetricsManifest(),
            ...$this->getDocsManifest(),
            ...$this->getOnboardingManifest(),
        ];
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
        // {tool, args} envelope routes to the metrics aggregator. Kept entirely
        // separate from the entity-history flow so the existing audit response
        // shape stays untouched for the FE renderer.
        if (isset($data['tool']) && is_string($data['tool']) && $data['tool'] !== '') {
            return $this->runTool($data['tool'], is_array($data['args'] ?? NULL) ? $data['args'] : []);
        }

        if (!isset($data['audit']) || !isset($data['data']) || !is_array($data['data'])) {
            throw new LogicException('MCP run payload must contain either {tool, args} or {audit, data}.');
        }

        $audit      = $data['audit'];
        $searchData = $data['data'];

        // Optional top-level date range — narrows topology runs to a window.
        // The resolver throws LogicException on invalid input which surfaces
        // to the user as a short Trace error.
        [$start, $end] = DateRangeResolver::resolve([
            DateRangeResolver::KEY_DAY    => $data[DateRangeResolver::KEY_DAY] ?? NULL,
            DateRangeResolver::KEY_FROM   => $data[DateRangeResolver::KEY_FROM] ?? NULL,
            DateRangeResolver::KEY_PERIOD => $data[DateRangeResolver::KEY_PERIOD] ?? NULL,
            DateRangeResolver::KEY_TO     => $data[DateRangeResolver::KEY_TO] ?? NULL,
        ], 30);

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

        $progressCriteria = ['auditData' => ['$in' => $auditDataIds]];
        $rangeCriteria    = ['$gte' => $start];
        if ($end !== NULL) {
            $rangeCriteria['$lt'] = $end;
        }
        $progressCriteria['startedAt'] = $rangeCriteria;

        $progresses = $this->topologyProgressRepository->findBy($progressCriteria);

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

        $logs = $this->lokiManager->queryAuditCheckpointsByCorrelationIds($correlationIds, $start, $end);

        return [
            'entity'      => $audit,
            'identifier'  => $searchData,
            'identifiers' => $identifiers,
            'runs'        => $this->groupLogsIntoRuns($logs, $progresses),
        ];
    }

    /**
     * Manifest entry for the onboarding_step tool. Only emitted when the
     * Nuxt origin is configured (DOCS_SEARCH_URL set) — same gating as the
     * docs tools, since both rely on the public Orchesty site to host the
     * source content.
     *
     * @return mixed[]
     */
    private function getOnboardingManifest(): array
    {
        if (!$this->onboardingStepClient->isConfigured()) {
            return [];
        }

        return [
            [
                'description'   => 'Return a structured onboarding step (title, intro, prerequisites, next, actions[]) for guiding a new Orchesty user through scaffolding a worker, building their first topology, running it locally and verifying the result. ALWAYS prefer this tool over docs_search when the user expresses onboarding intent ("how do I start", "jak začít", "first time", "co je dál", "what\'s next"). Args: optional `stage` — when missing, returns the first step. After receiving a step, the assistant renders it as plain text with [shell] / [prompt] / [link] action blocks the FE detects and shows as copy-pasteable cards.',
                'id'            => 'onboarding_step',
                'input_schema'  => [
                    'properties' => [
                        'stage' => [
                            'description' => 'Onboarding stage id (e.g. "overview", "choose-your-way", "clone-starter-ai", "clone-starter-manual", "build-components-ai", "build-components-manual", "run-locally", "test-and-debug", "verify", "add-a-node", "application", "connector-node", "batch-node", "custom-node", "webhook-trigger", "event-trigger", "cron-trigger"). Omit to start from the first stage.',
                            'type'        => 'string',
                        ],
                    ],
                    'required'   => [],
                    'type'       => 'object',
                ],
                'kind'          => 'onboarding',
                'output_schema' => [
                    'properties' => [
                        'actions'       => [
                            'items' => [
                                'properties' => [
                                    'href'  => ['type' => 'string'],
                                    'kind'  => ['enum' => ['shell', 'prompt', 'link'], 'type' => 'string'],
                                    'label' => ['type' => 'string'],
                                    'value' => ['type' => 'string'],
                                ],
                                'type'       => 'object',
                            ],
                            'type'  => 'array',
                        ],
                        'description'   => ['type' => 'string'],
                        'intro'         => ['type' => 'string'],
                        'next'          => ['type' => 'string'],
                        'path'          => ['type' => 'string'],
                        'prerequisites' => [
                            'items' => ['type' => 'string'],
                            'type'  => 'array',
                        ],
                        'stage'         => ['type' => 'string'],
                        'stages'        => [
                            'items' => ['type' => 'string'],
                            'type'  => 'array',
                        ],
                        'title'         => ['type' => 'string'],
                    ],
                    'type'       => 'object',
                ],
                'title'         => 'Get an interactive onboarding step',
            ],
        ];
    }

    /**
     * Manifest entry for the docs_search tool. Only emitted when the
     * DocsSearchClient is configured (DOCS_SEARCH_URL set) — otherwise the
     * Trace LLM would see a tool that always errors out, which leads it to
     * route platform-usage questions through the catch-all Reply shape and
     * apologise to the user instead of either answering or staying silent.
     *
     * @return mixed[]
     */
    private function getDocsManifest(): array
    {
        if (!$this->docsSearchClient->isConfigured()) {
            return [];
        }

        $manifest = [];

        $manifest[] = [
            'description'   => 'Search the public Orchesty documentation (orchesty.io) for platform usage answers. Use for questions like "how do I get started", "how do I create a topology", "how does OAuth2 application setup work", "what is a connector", any "how do I…" / "what is…" / "jak nastavím…" question. Pass the user message verbatim as `query`. Do NOT use this tool for entity history or metrics — those have their own envelopes. The top 1–2 results carry a `bodyExcerpt` field (~3500 chars) so the assistant can ground its answer in actual page text rather than just listing links. If the excerpts do not answer the question, follow up with a single docs_read call against the most relevant `path`.',
            'id'            => 'docs_search',
            'input_schema'  => [
                'properties' => [
                    'locale' => [
                        'description' => 'Preferred reply language for the user. Hint only — corpus is single-language.',
                        'enum'        => ['cs', 'en'],
                        'type'        => 'string',
                    ],
                    'query'  => [
                        'description' => 'Natural-language question about Orchesty platform usage. Pass the user message verbatim.',
                        'type'        => 'string',
                    ],
                ],
                'required'   => ['query'],
                'type'       => 'object',
            ],
            'kind'          => 'docs',
            'output_schema' => [
                'properties' => [
                    'latestVersion' => ['type' => 'string'],
                    'query'         => ['type' => 'string'],
                    'results'       => [
                        'items' => [
                            'properties' => [
                                'bodyExcerpt' => [
                                    'description' => 'Up to ~3500 chars of actual page text, present only on the top 1–2 results. Use it verbatim or paraphrased to ground the answer.',
                                    'type'        => 'string',
                                ],
                                'description' => ['type' => 'string'],
                                'path'        => ['type' => 'string'],
                                'score'       => ['type' => 'number'],
                                'snippet'     => ['type' => 'string'],
                                'source'      => ['enum' => ['docs', 'learn', 'onboarding'], 'type' => 'string'],
                                'title'       => ['type' => 'string'],
                            ],
                            'type'       => 'object',
                        ],
                        'type'  => 'array',
                    ],
                ],
                'type'       => 'object',
            ],
            'title'         => 'Search Orchesty documentation',
        ];

        if ($this->docsReadClient->isConfigured()) {
            $manifest[] = [
                'description'   => 'Fetch the full body text (up to ~12000 chars) of a single Orchesty documentation, learn or onboarding page. Use only as a follow-up to docs_search when the top result\'s `bodyExcerpt` is too thin to answer the user\'s question. Pass the canonical `path` returned by docs_search (e.g. "/docs/2.0/...", "/learn/...", "/onboarding/..."). Call this AT MOST ONCE per user turn.',
                'id'            => 'docs_read',
                'input_schema'  => [
                    'properties' => [
                        'path' => [
                            'description' => 'Canonical page path returned by docs_search.',
                            'type'        => 'string',
                        ],
                    ],
                    'required'   => ['path'],
                    'type'       => 'object',
                ],
                'kind'          => 'docs',
                'output_schema' => [
                    'properties' => [
                        'body'          => ['type' => 'string'],
                        'description'   => ['type' => 'string'],
                        'latestVersion' => ['type' => 'string'],
                        'path'          => ['type' => 'string'],
                        'title'         => ['type' => 'string'],
                        'truncated'     => ['type' => 'boolean'],
                    ],
                    'type'       => 'object',
                ],
                'title'         => 'Read a single Orchesty documentation page',
            ];
        }

        return $manifest;
    }

    /**
     * Fixed actions the LLM can call for cross-cutting metrics questions.
     * They sit alongside the per-entity audit history so the model can pick
     * the right tool: entity-specific timelines go through `entity_history`,
     * "how many processes" / "which connector fails most" go through these.
     *
     * @return mixed[]
     */
    private function getMetricsManifest(): array
    {
        $dateProperties = [
            'from'   => [
                'description' => 'ISO 8601 start of the range (inclusive). Use with "to".',
                'format'      => 'date-time',
                'type'        => 'string',
            ],
            'period' => [
                'description' => 'Named relative range: today, yesterday, this_week, last_7d, last_30d.',
                'enum'        => ['today', 'yesterday', 'this_week', 'last_7d', 'last_30d'],
                'type'        => 'string',
            ],
            'to'     => [
                'description' => 'ISO 8601 end of the range (exclusive). Use with "from".',
                'format'      => 'date-time',
                'type'        => 'string',
            ],
        ];

        return [
            [
                'description'   => 'Aggregated process counts (success/failed) bucketed over a time range. Use for questions like "how many processes ran last week" or "what is the failure rate today". Do NOT use for per-entity history; route those through the entity_history actions.',
                'id'            => 'processes_timeseries',
                'input_schema'  => [
                    'properties' => [
                        ...$dateProperties,
                        'buckets'     => [
                            'description' => 'Bucket count between 1 and 24 (default 12).',
                            'maximum'     => 24,
                            'minimum'     => 1,
                            'type'        => 'integer',
                        ],
                        'topology_id' => [
                            'description' => 'Optional topology id to restrict the aggregation to one topology.',
                            'type'        => 'string',
                        ],
                    ],
                    'type'       => 'object',
                ],
                'kind'          => 'timeseries',
                'output_schema' => [
                    'properties' => [
                        'failed' => ['type' => 'integer'],
                        'kind'   => ['type' => 'string'],
                        'period' => ['type' => 'string'],
                        'points' => [
                            'items' => [
                                'properties' => [
                                    'failed'  => ['type' => 'integer'],
                                    'success' => ['type' => 'integer'],
                                    'time'    => ['type' => 'string'],
                                ],
                                'type'       => 'object',
                            ],
                            'type'  => 'array',
                        ],
                        'title'  => ['type' => 'string'],
                        'total'  => ['type' => 'integer'],
                    ],
                    'type'       => 'object',
                ],
                'title'         => 'Process counts over time',
            ],
            [
                'description'   => 'Lists topologies that had at least one process run in the time range. Use for "which topologies were running today", "what topologies were active last week", "show me running topologies", "jaké topologie běžely". Each item carries the topology name, total run count and how many of those runs succeeded, failed or are still in flight, plus the first and last run timestamps in the window. Use processes_timeseries instead when the user asks about MESSAGE volumes over time, not which topologies executed.',
                'id'            => 'topologies_activity',
                'input_schema'  => [
                    'properties' => [
                        ...$dateProperties,
                        'limit' => [
                            'description' => 'Maximum number of topologies to return (1-50, default 10).',
                            'maximum'     => 50,
                            'minimum'     => 1,
                            'type'        => 'integer',
                        ],
                    ],
                    'type'       => 'object',
                ],
                'kind'          => 'list',
                'output_schema' => [
                    'properties' => [
                        'items'  => [
                            'items' => [
                                'properties' => [
                                    'failed'       => ['type' => 'integer'],
                                    'firstRunAt'   => ['type' => ['string', 'null']],
                                    'lastRunAt'    => ['type' => ['string', 'null']],
                                    'running'      => ['type' => 'integer'],
                                    'runs'         => ['type' => 'integer'],
                                    'success'      => ['type' => 'integer'],
                                    'topologyId'   => ['type' => 'string'],
                                    'topologyName' => ['type' => 'string'],
                                ],
                                'type'       => 'object',
                            ],
                            'type'  => 'array',
                        ],
                        'kind'   => ['type' => 'string'],
                        'period' => ['type' => 'string'],
                        'title'  => ['type' => 'string'],
                    ],
                    'type'       => 'object',
                ],
                'title'         => 'Topologies active in range',
            ],
            [
                'description'   => 'Top connectors by failure count over a time range. Use for "which connector is failing", "show flaky connectors today" type questions. Returns a short list ranked by failure count (descending).',
                'id'            => 'failing_connectors',
                'input_schema'  => [
                    'properties' => [
                        ...$dateProperties,
                        'limit' => [
                            'description' => 'Maximum number of connectors to return (1-20, default 10).',
                            'maximum'     => 20,
                            'minimum'     => 1,
                            'type'        => 'integer',
                        ],
                    ],
                    'type'       => 'object',
                ],
                'kind'          => 'list',
                'output_schema' => [
                    'properties' => [
                        'items'  => [
                            'items' => [
                                'properties' => [
                                    'failed'       => ['type' => 'integer'],
                                    'failureRate'  => ['type' => 'number'],
                                    'nodeName'     => ['type' => 'string'],
                                    'success'      => ['type' => 'integer'],
                                    'topologyName' => ['type' => 'string'],
                                ],
                                'type'       => 'object',
                            ],
                            'type'  => 'array',
                        ],
                        'kind'   => ['type' => 'string'],
                        'period' => ['type' => 'string'],
                        'title'  => ['type' => 'string'],
                    ],
                    'type'       => 'object',
                ],
                'title'         => 'Top failing connectors',
            ],
            [
                'description'   => 'Most recent failed connector calls over a time range, sourced from the same connector metrics that power the dashboard process detail. Use for "show the last errors", "what failed today", "recent connector errors" type questions. Each item carries the failing node, topology, the truncated upstream response body (`resultMessage`) and the HTTP status the bridge observed. Soft SDK outcomes (repeat / limit / trashed without an HTTP call) are NOT included here — only real HTTP-level connector failures.',
                'id'            => 'recent_errors',
                'input_schema'  => [
                    'properties' => [
                        ...$dateProperties,
                        'limit'       => [
                            'description' => 'Maximum number of error entries to return (1-20, default 10).',
                            'maximum'     => 20,
                            'minimum'     => 1,
                            'type'        => 'integer',
                        ],
                        'topology_id' => [
                            'description' => 'Optional topology id to restrict the search to one topology.',
                            'type'        => 'string',
                        ],
                    ],
                    'type'       => 'object',
                ],
                'kind'          => 'list',
                'output_schema' => [
                    'properties' => [
                        'items'  => [
                            'items' => [
                                'properties' => [
                                    'correlationId' => ['type' => 'string'],
                                    'finishedAt'    => ['type' => 'string'],
                                    'httpStatus'    => ['type' => ['integer', 'null']],
                                    'nodeName'      => ['type' => 'string'],
                                    'resultMessage' => ['type' => 'string'],
                                    'resultStatus'  => ['type' => 'string'],
                                    'topologyName'  => ['type' => 'string'],
                                ],
                                'type'       => 'object',
                            ],
                            'type'  => 'array',
                        ],
                        'kind'   => ['type' => 'string'],
                        'period' => ['type' => 'string'],
                        'title'  => ['type' => 'string'],
                    ],
                    'type'       => 'object',
                ],
                'title'         => 'Recent errors',
            ],
        ];
    }

    /**
     * Routes `{tool, args}` envelopes to the metrics aggregator. Kept private
     * so the public surface stays a single `run()` entry point regardless of
     * which envelope shape arrived.
     *
     * @param string  $tool
     * @param mixed[] $args
     *
     * @return mixed[]
     */
    private function runTool(string $tool, array $args): array
    {
        return match ($tool) {
            'processes_timeseries' => $this->metricsAggregator->getProcessesTimeseries($args),
            'topologies_activity'  => $this->metricsAggregator->getTopologiesActivity($args),
            'failing_connectors'   => $this->metricsAggregator->getFailingConnectors($args),
            'recent_errors'        => $this->metricsAggregator->getRecentErrors($args),
            'docs_search'          => $this->docsSearchClient->search(
                (string) ($args['query'] ?? ''),
                isset($args['topK']) && is_numeric($args['topK'])
                    ? (int) $args['topK']
                    : DocsSearchClient::DEFAULT_TOP_K,
                isset($args['locale']) && is_string($args['locale']) ? $args['locale'] : NULL,
            ),
            'docs_read'            => $this->docsReadClient->read((string) ($args['path'] ?? '')),
            'onboarding_step'      => $this->onboardingStepClient->step(
                isset($args['stage']) && is_string($args['stage']) ? $args['stage'] : NULL,
            ),
            default                => throw new LogicException(sprintf('Unknown MCP tool "%s".', $tool)),
        };
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
     * `{ entry, steps[], exit }` and enriches each run with the basic
     * `TopologyProgress` snapshot (topology name, start/end, ok/nok/total,
     * derived progress status).
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
     * still appear (with null entry/exit and empty steps) — the UI uses
     * `progressStatus` + `startedAt`/`finishedAt` to fall back to a
     * meaningful status/timeline even on non-instrumented topologies.
     *
     * @param array<int, array<string, mixed>> $logs
     * @param TopologyProgress[]               $progresses
     *
     * @return array<int, array<string, mixed>>
     */
    private function groupLogsIntoRuns(array $logs, array $progresses): array
    {
        $topologyNames = $this->resolveTopologyNames($progresses);

        $runs = [];

        foreach ($progresses as $progress) {
            $topologyId = $progress->getTopologyId();
            $started    = $progress->getStartedAt();
            $finished   = $progress->getFinishedAt();
            $ok         = $progress->getOk();
            $nok        = $progress->getNok();
            $total      = $progress->getTotal();

            $runs[$progress->getId()] = [
                'correlationId'  => $progress->getId(),
                'entry'          => NULL,
                'exit'           => NULL,
                'finishedAt'     => $finished?->format(DATE_ATOM),
                'nok'            => $nok,
                'ok'             => $ok,
                // Derived from progress counters: gives the FE a meaningful
                // status pill even when the topology emits no audit
                // checkpoints (which is the common case today).
                'progressStatus' => $this->deriveProgressStatus($finished, $ok, $nok, $total),
                'startedAt'      => $started->format(DATE_ATOM),
                'steps'          => [],
                'topologyId'     => $topologyId,
                'topologyName'   => $topologyNames[$topologyId] ?? NULL,
                'total'          => $total,
            ];
        }

        foreach ($logs as $log) {
            $cid = $log['correlationId'] ?? '';

            if ($cid === '') {
                continue;
            }

            if (!isset($runs[$cid])) {
                $runs[$cid] = [
                    'correlationId'  => $cid,
                    'entry'          => NULL,
                    'exit'           => NULL,
                    'finishedAt'     => NULL,
                    'nok'            => 0,
                    'ok'             => 0,
                    'progressStatus' => 'unknown',
                    'startedAt'      => NULL,
                    'steps'          => [],
                    'topologyId'     => $log['topologyId'] ?? NULL,
                    'topologyName'   => $log['topologyName'] ?? NULL,
                    'total'          => 0,
                ];
            } else {
                if (($runs[$cid]['topologyId'] ?? NULL) === NULL && isset($log['topologyId'])) {
                    $runs[$cid]['topologyId'] = $log['topologyId'];
                }

                if (($runs[$cid]['topologyName'] ?? NULL) === NULL && isset($log['topologyName'])) {
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

    /**
     * Resolves topology names for the supplied progresses in a single Mongo
     * query keyed by topologyId. Returned map: `topologyId => topologyName`.
     * Topologies that no longer exist (deleted versions) are silently skipped
     * so the run still renders with a `null` name.
     *
     * @param TopologyProgress[] $progresses
     *
     * @return array<string, string>
     */
    private function resolveTopologyNames(array $progresses): array
    {
        $ids = [];
        foreach ($progresses as $progress) {
            $tid = $progress->getTopologyId();
            if ($tid !== '' && !isset($ids[$tid])) {
                $ids[$tid] = TRUE;
            }
        }

        if ($ids === []) {
            return [];
        }

        /** @var Topology[] $topologies */
        $topologies = $this->topologyRepository->findBy(['_id' => ['$in' => array_keys($ids)]]);

        $names = [];
        foreach ($topologies as $topology) {
            $names[$topology->getId()] = $topology->getName();
        }

        return $names;
    }

    /**
     * Derives a high-level progress status from the bridge counters so the
     * FE can render a meaningful pill on runs that do not emit audit
     * checkpoints (entry/exit are NULL but `nok > 0` still says "failed").
     *
     * - `success` — finished and every step succeeded
     * - `failed`  — finished and at least one step failed
     * - `running` — not finished yet
     * - `unknown` — no usable counters (defensive default)
     *
     * @param DateTimeInterface|null $finished
     * @param int                    $ok
     * @param int                    $nok
     * @param int                    $total
     *
     * @return string
     */
    private function deriveProgressStatus(?DateTimeInterface $finished, int $ok, int $nok, int $total): string
    {
        if ($finished === NULL) {
            return 'running';
        }

        if ($total === 0 && $ok === 0 && $nok === 0) {
            return 'unknown';
        }

        return $nok > 0 ? 'failed' : 'success';
    }

}
