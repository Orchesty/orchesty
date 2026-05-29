<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Application\Document\Webhook;
use Hanaboso\PipesFramework\Application\Document\WebhookConfig;
use Hanaboso\PipesFramework\Application\Repository\WebhookConfigRepository;
use Hanaboso\PipesFramework\Application\Repository\WebhookRepository;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\Utils\Exception\DateTimeException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Class WebhookConfigManager
 *
 * @package Hanaboso\PipesFramework\Application\Manager
 *
 * Persists user-defined webhook configuration (created in the topology editor)
 * and orchestrates the actual subscribe / unsubscribe call against the worker SDK.
 *
 * The split between {@see WebhookConfig} (intent) and {@see Webhook} (live registration)
 * lets the UI show a node as "configured" before any external API call has been made
 * and lets us cleanly retry / cascade subscriptions.
 */
final class WebhookConfigManager
{

    /**
     * @var ObjectRepository<WebhookConfig>&WebhookConfigRepository
     */
    private WebhookConfigRepository $configRepository;

    /**
     * @var ObjectRepository<Webhook>&WebhookRepository
     */
    private WebhookRepository $webhookRepository;

    /**
     * WebhookConfigManager constructor.
     *
     * @param DocumentManager $dm
     * @param ServiceLocator  $locator
     */
    public function __construct(private readonly DocumentManager $dm, private readonly ServiceLocator $locator)
    {
        $this->configRepository  = $this->dm->getRepository(WebhookConfig::class);
        $this->webhookRepository = $this->dm->getRepository(Webhook::class);
    }

    /**
     * @param string $topologyName
     *
     * @return mixed[]
     */
    public function listForTopology(string $topologyName): array
    {
        $configs = $this->configRepository->findByTopology($topologyName);
        // Only count live (non soft-deleted) registrations — worker-api soft-
        // deletes Webhook docs by stamping `deleted` with an ISODate, but the
        // PHP document doesn't map that field, so a plain findBy() would
        // include unsubscribed entries and the UI would still report
        // "Subscribed".
        $webhooks = $this->webhookRepository->findActiveByTopology($topologyName);

        // Orphan = subscribed webhook (config or live registration) whose
        // `nodeName` is not present in the **currently enabled** version of
        // the topology. That is the only version that actually consumes
        // events at runtime — registrations not pointed at it are ghosts
        // burning external API quota with nowhere to deliver.
        //
        // If no version of the topology is enabled (draft, paused for
        // editing, freshly cloned), we deliberately do *not* mark anything
        // as orphan. Otherwise simply disabling a topology would scream at
        // the user with a banner that suggests cleaning up perfectly valid
        // registrations they will need again on the next enable.
        $enabledWebhookNodeNames = $this->getEnabledWebhookNodeNames($topologyName);

        // Index live registrations by (node, name) to enrich the response.
        $live = [];
        foreach ($webhooks as $webhook) {
            $live[sprintf('%s|%s', $webhook->getNode(), $webhook->getName())] = $webhook;
        }

        $items = [];
        foreach ($configs as $config) {
            $key        = sprintf('%s|%s', $config->getNodeName(), $config->getEventName());
            $registered = $live[$key] ?? NULL;
            $isOrphan   = $enabledWebhookNodeNames !== NULL
                && !in_array($config->getNodeName(), $enabledWebhookNodeNames, TRUE);
            $items[]    = $config->toArray() + [
                'orphan'            => $isOrphan,
                'registered'        => $registered !== NULL,
                'token'             => $registered?->getToken() ?? '',
                'unsubscribeFailed' => $registered?->isUnsubscribeFailed() ?? FALSE,
                'webhookId'         => $registered?->getWebhookId() ?? '',
            ];
            unset($live[$key]);
        }

        // Surface live registrations that no longer have a config — they
        // are by definition orphan, but we still respect the "no enabled
        // version → no orphan banner" rule so the user is not pushed to
        // clean up registrations during routine disable/enable cycles.
        if ($enabledWebhookNodeNames !== NULL) {
            foreach ($live as $orphan) {
                $items[] = [
                    'application'       => $orphan->getApplication(),
                    'enabled'           => TRUE,
                    'eventName'         => $orphan->getName(),
                    'nodeName'          => $orphan->getNode(),
                    'orphan'            => TRUE,
                    'parameters'        => [],
                    'registered'        => TRUE,
                    'sdk'               => $orphan->getSdk(),
                    'token'             => $orphan->getToken(),
                    'topologyName'      => $orphan->getTopology(),
                    'unsubscribeFailed' => $orphan->isUnsubscribeFailed(),
                    'user'              => $orphan->getUser(),
                    'webhookId'         => $orphan->getWebhookId(),
                ];
            }
        }

        return $items;
    }

    /**
     * Upsert (create or update) a single webhook config for the given (topology, node).
     *
     * @param string               $topologyName
     * @param string               $nodeName
     * @param array<string, mixed> $payload      Expected keys:
     *                                            - application (required)
     *                                            - user (required)
     *                                            - sdk (required)
     *                                            - eventName (required)
     *                                            - parameters (optional, hash)
     *                                            - enabled (optional, bool)
     *
     * @return WebhookConfig
     * @throws DateTimeException
     */
    public function upsertConfig(string $topologyName, string $nodeName, array $payload): WebhookConfig
    {
        $this->requireKeys($payload, ['application', 'user', 'sdk', 'eventName']);

        $config = $this->configRepository->findByTopologyAndNode($topologyName, $nodeName);
        $isNew  = FALSE;

        if (!$config) {
            $config = (new WebhookConfig())
                ->setTopologyName($topologyName)
                ->setNodeName($nodeName);
            $isNew  = TRUE;
        }

        $config
            ->setApplication((string) $payload['application'])
            ->setUser((string) $payload['user'])
            ->setSdk((string) $payload['sdk'])
            ->setEventName((string) $payload['eventName'])
            ->setParameters((array) ($payload['parameters'] ?? []))
            ->setEnabled((bool) ($payload['enabled'] ?? FALSE));

        if ($isNew) {
            $this->dm->persist($config);
        }
        $this->dm->flush();

        return $config;
    }

    /**
     * Removes the webhook config for (topology, node). If a live registration
     * exists, unsubscribe is attempted first; failure leaves the registration
     * marked as `unsubscribeFailed` for manual cleanup.
     *
     * @param string $topologyName
     * @param string $nodeName
     *
     * @return void
     */
    public function deleteConfig(string $topologyName, string $nodeName): void
    {
        $config = $this->configRepository->findByTopologyAndNode($topologyName, $nodeName);

        if ($config && $this->isRegistered($config)) {
            try {
                $this->unsubscribe($config);
            } catch (Throwable) {
                // The unsubscribe error is already logged downstream; we still
                // remove the config so the UI is not stuck on a stale entry.
            }
        }

        if ($config) {
            $this->dm->remove($config);
            $this->dm->flush();
        }
    }

    /**
     * @param WebhookConfig $config
     *
     * @return mixed[]
     */
    public function subscribe(WebhookConfig $config): array
    {
        // Idempotency guard: do not re-call the SDK if a live registration
        // already exists for this config. This means repeated UI clicks (or
        // double POSTs from the API gateway) will not create duplicate webhooks
        // upstream. The caller still gets a success response with `noop: true`
        // so it can refresh state without surfacing an error.
        if ($this->isRegistered($config)) {
            $config->setEnabled(TRUE);
            $this->dm->flush();

            return ['noop' => TRUE, 'reason' => 'already-subscribed'];
        }

        // Self-heal stale SDK names. The Node document used to be saved with
        // the application's display title in the `sdk` field (e.g. "Webhook
        // Test webhook" instead of "test-worker"); upsertFromNode then
        // copied that bogus value into the WebhookConfig and the upstream
        // ServiceLocator could not route to any registered SDK. Resolve the
        // SDK from the installed application — this is also future-proof if
        // the application is moved between SDK runtimes.
        $this->ensureValidSdk($config);

        $config->setEnabled(TRUE);
        $this->dm->flush();

        // Pass `throw=true` so SDK / transport errors propagate to the caller
        // instead of being silently swallowed by ServiceLocator::doRequest. The
        // controller/UI relies on the exception to surface the actual reason
        // (e.g. ApplicationInstall missing, application not registered on the
        // selected SDK) instead of reporting a false positive "subscribed".
        return $this->locator->subscribeWebhook(
            $config->getApplication(),
            $config->getUser(),
            $config->getSdk(),
            [
                'name'       => $config->getEventName(),
                'node'       => $config->getNodeName(),
                'parameters' => $config->getParameters(),
                'topology'   => $config->getTopologyName(),
            ],
            TRUE,
        );
    }

    /**
     * @param WebhookConfig $config
     *
     * @return mixed[]
     */
    public function unsubscribe(WebhookConfig $config): array
    {
        // Idempotency guard: if no live registration is active, the caller is
        // effectively asking us to confirm the unsubscribed state — do not
        // touch the SDK and just keep the config flag in sync.
        if (!$this->isRegistered($config)) {
            $config->setEnabled(FALSE);
            $this->dm->flush();

            return ['noop' => TRUE, 'reason' => 'already-unsubscribed'];
        }

        $this->ensureValidSdk($config);

        $result = $this->locator->unSubscribeWebhook(
            $config->getApplication(),
            $config->getUser(),
            $config->getSdk(),
            [
                'name'     => $config->getEventName(),
                'node'     => $config->getNodeName(),
                'topology' => $config->getTopologyName(),
            ],
            TRUE,
        );

        $config->setEnabled(FALSE);
        $this->dm->flush();

        return $result;
    }

    /**
     * UI-facing subscribe entry point. The user only ever sees a webhook node
     * with an action "Subscribe" — they are not aware that a {@see WebhookConfig}
     * document exists. This method therefore lazily materialises the config
     * from the corresponding Node on first use, optionally storing custom
     * subscribe parameters supplied by the caller (currently the Subscribe
     * modal in `TopologyEditor.vue`). Subsequent calls reuse the existing
     * config and only update parameters if a new value is provided.
     *
     * Idempotent: a repeated call against an already-registered webhook
     * returns `{ noop: true, reason: 'already-subscribed' }` and does not
     * re-issue the upstream SDK request (see {@see subscribe}).
     *
     * @param string                    $topologyName
     * @param string                    $nodeName
     * @param array<string, mixed>|null $parameters
     * @param string                    $user
     *
     * @return mixed[]
     *
     * @throws DateTimeException
     * @throws RuntimeException When no webhook node with the given name
     *                          exists in the latest version of the topology.
     */
    public function subscribeForNode(
        string $topologyName,
        string $nodeName,
        ?array $parameters = NULL,
        string $user = 'orchesty',
    ): array
    {
        $config = $this->configRepository->findByTopologyAndNode($topologyName, $nodeName);

        if (!$config) {
            $node = $this->resolveLatestWebhookNode($topologyName, $nodeName);
            if ($node === NULL) {
                throw new RuntimeException(
                    sprintf(
                        'No webhook node "%s" found in any version of topology "%s".',
                        $nodeName,
                        $topologyName,
                    ),
                );
            }

            // Node::getTopology() returns the parent topology id as string;
            // hydrate the document so upsertFromNode can read the topology
            // name (which is what the WebhookConfig is actually keyed by).
            /** @var Topology|null $topology */
            $topology = $this->dm->getRepository(Topology::class)->find($node->getTopology());
            if ($topology === NULL) {
                throw new RuntimeException(sprintf('Webhook node "%s" is detached from its topology.', $nodeName));
            }

            $config = $this->upsertFromNode($topology, $node, $user);
            if ($config === NULL) {
                throw new RuntimeException(
                    sprintf('Webhook node "%s" is missing application/eventName metadata.', $nodeName),
                );
            }
        }

        if ($parameters !== NULL) {
            $config->setParameters($parameters);
            $this->dm->flush();
        }

        return $this->subscribe($config);
    }

    /**
     * UI-facing unsubscribe entry point. Mirrors {@see subscribeForNode}: the
     * caller only knows `(topology, node)` and never touches the underlying
     * config document. Behaviour is fully idempotent — calling with no
     * existing config or no live registration returns success with a `noop`
     * marker rather than 4xx.
     *
     * @param string $topologyName
     * @param string $nodeName
     *
     * @return mixed[]
     */
    public function unsubscribeForNode(string $topologyName, string $nodeName): array
    {
        $config = $this->configRepository->findByTopologyAndNode($topologyName, $nodeName);

        if (!$config) {
            return ['noop' => TRUE, 'reason' => 'no-config'];
        }

        return $this->unsubscribe($config);
    }

    /**
     * Subscribe / unsubscribe every WebhookConfig of the topology — used as the
     * cascade hook when the topology is enabled or disabled in the UI.
     *
     * @param string $topologyName
     * @param bool   $enable
     *
     * @return mixed[] Per-config result entries (status + payload).
     */
    public function cascadeForTopology(string $topologyName, bool $enable): array
    {
        $results = [];
        foreach ($this->configRepository->findByTopology($topologyName) as $config) {
            try {
                $payload   = $enable
                    ? $this->subscribe($config)
                    : $this->unsubscribe($config);
                $results[] = [
                    'nodeName'     => $config->getNodeName(),
                    'payload'      => $payload,
                    'status'       => 'ok',
                    'topologyName' => $config->getTopologyName(),
                ];
            } catch (Throwable $t) {
                $results[] = [
                    'message'      => $t->getMessage(),
                    'nodeName'     => $config->getNodeName(),
                    'status'       => 'error',
                    'topologyName' => $config->getTopologyName(),
                ];
            }
        }

        return $results;
    }

    /**
     * @return WebhookConfigRepository
     */
    public function getRepository(): WebhookConfigRepository
    {
        return $this->configRepository;
    }

    /**
     * Schema-save hook: create or update the WebhookConfig document derived
     * from a Webhook node already persisted by {@see TopologyManager}.
     *
     * The node carries `application`, `sdk` and `eventName` populated by the
     * editor's picker (`action.app/worker/name`). We deliberately keep the
     * `enabled` flag on existing configs so a republish does not silently
     * disable a previously subscribed webhook.
     *
     * @param Topology $topology
     * @param Node     $node
     * @param string   $user
     *
     * @return WebhookConfig|null Returns null if the node is not a webhook node
     *                            or is missing required identity fields.
     *
     * @throws DateTimeException
     */
    public function upsertFromNode(Topology $topology, Node $node, string $user): ?WebhookConfig
    {
        if ($node->getType() !== TypeEnum::WEBHOOK->value) {
            return NULL;
        }

        $eventName   = $node->getEventName();
        $application = $node->getApplication() ?? '';
        if ($eventName === '' || $application === '') {
            return NULL;
        }

        $config = $this->configRepository->findByTopologyAndNode($topology->getName(), $node->getName());
        $isNew  = FALSE;

        if (!$config) {
            $config = (new WebhookConfig())
                ->setTopologyName($topology->getName())
                ->setNodeName($node->getName())
                ->setEnabled(FALSE);
            $isNew  = TRUE;
        }

        $config
            ->setApplication($application)
            ->setUser($user)
            ->setSdk($node->getSdk())
            ->setEventName($eventName);

        if ($isNew) {
            $this->dm->persist($config);
        }
        $this->dm->flush();

        return $config;
    }

    /**
     * Schema-save hook: cascade delete of a WebhookConfig (and best-effort
     * unsubscribe of the live registration) for a node that has just been
     * removed from the topology schema.
     *
     * @param Topology $topology
     * @param Node     $node
     *
     * @return void
     */
    public function deleteForNode(Topology $topology, Node $node): void
    {
        if ($node->getType() !== TypeEnum::WEBHOOK->value) {
            return;
        }

        $this->deleteConfig($topology->getName(), $node->getName());
    }

    /**
     * Webhook node names that exist in the **currently enabled** version of
     * the topology. Returns `NULL` when no enabled version exists — the
     * caller must treat that as "do not perform orphan checks", because
     * runtime would not deliver events to *any* version anyway and we do
     * not want to scare the user into deleting registrations during a
     * temporary disable.
     *
     * @param string $topologyName
     *
     * @return string[]|null
     */
    private function getEnabledWebhookNodeNames(string $topologyName): ?array
    {
        $nodes = $this->getEnabledWebhookNodes($topologyName);
        if ($nodes === NULL) {
            return NULL;
        }

        $names = [];
        foreach ($nodes as $node) {
            $names[] = $node->getName();
        }

        return $names;
    }

    /**
     * Locates a single webhook-typed node by name across **any** version of
     * the named topology. The user-facing detail page can target any version
     * (not just the latest), so a strict latest-only lookup falsely refuses
     * to subscribe webhooks that exist in earlier published versions. Since
     * `WebhookConfig` is keyed by `(topologyName, nodeName)` — which is
     * stable across versions — we only need *some* matching `Node` document
     * to read the application / eventName / sdk metadata for the lazy
     * upsert. The latest matching node is preferred so freshly-edited
     * metadata wins on a tie.
     *
     * @param string $topologyName
     * @param string $nodeName
     *
     * @return Node|null
     */
    private function resolveLatestWebhookNode(string $topologyName, string $nodeName): ?Node
    {
        /** @var Topology[] $topologies */
        $topologies = $this->dm->getRepository(Topology::class)->findBy(
            ['name' => $topologyName],
            ['version' => 'desc'],
        );

        if ($topologies === []) {
            return NULL;
        }

        $topologyIds = array_map(static fn(Topology $t): string => $t->getId(), $topologies);

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy([
            'name'     => $nodeName,
            'topology' => ['$in' => $topologyIds],
            'type'     => TypeEnum::WEBHOOK->value,
        ]);

        if ($nodes === []) {
            return NULL;
        }

        // Order by topology version descending. The repository find above
        // returns nodes without a deterministic order, so we re-sort using
        // the version map we already loaded.
        $versionByTopologyId = [];
        foreach ($topologies as $topology) {
            $versionByTopologyId[$topology->getId()] = $topology->getVersion();
        }

        usort(
            $nodes,
            static fn(Node $a, Node $b): int => ($versionByTopologyId[$a->getTopology()] ?? 0) <=> ($versionByTopologyId[$b->getTopology()] ?? 0),
        );

        return end($nodes);
    }

    // Webhook nodes from the currently enabled version of the topology.
    //
    // Returns `NULL` (not `[]`) when no enabled version exists, so callers
    // can distinguish "topology has no active version → skip orphan
    // detection" from "active version genuinely has zero webhook nodes →
    // every config is orphan". If multiple versions are flagged enabled
    // (should not happen by design, but DB invariants are not enforced)
    // the latest by `version` wins.
    /**
     * @param string $topologyName
     *
     * @return Node[]|null
     */
    private function getEnabledWebhookNodes(string $topologyName): ?array
    {
        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->findOneBy(
            [
                'enabled' => TRUE,
                'name'    => $topologyName,
            ],
            ['version' => 'desc'],
        );
        if ($topology === NULL) {
            return NULL;
        }

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy([
            'topology' => $topology->getId(),
            'type'     => TypeEnum::WEBHOOK->value,
        ]);

        return $nodes;
    }

    /**
     * Make sure the WebhookConfig points at a real SDK identifier known to
     * the ServiceLocator. Older Node documents (and configs derived from
     * them) sometimes carry the application's display title in `sdk`
     * instead of an actual SDK name like `test-worker` / `node-sdk`. When
     * we detect such a value we transparently resolve the correct SDK from
     * the application's installation and persist the fix so subsequent
     * calls (subscribe/unsubscribe) stay aligned.
     *
     * @param WebhookConfig $config
     *
     * @return void
     */
    private function ensureValidSdk(WebhookConfig $config): void
    {
        $registered = $this->locator->getSdks();
        $valid      = array_map(static fn($sdk) => $sdk->getName(), $registered);

        if (in_array($config->getSdk(), $valid, TRUE)) {
            return;
        }

        $resolved = $this->locator->getSdkNameByInstalledApplication($config->getApplication());
        $config->setSdk($resolved);
        $this->dm->flush();
    }

    /**
     * @param WebhookConfig $config
     *
     * @return bool
     */
    private function isRegistered(WebhookConfig $config): bool
    {
        // Must filter out soft-deleted Webhook records (worker-api flips the
        // `deleted` ISODate on unsubscribe). A plain `findOneBy` returns
        // those zombies and would lock the config into a permanent
        // "already-subscribed" state after the first round-trip.
        return $this->webhookRepository->findActiveOne(
            $config->getTopologyName(),
            $config->getNodeName(),
            $config->getEventName(),
        ) !== NULL;
    }

    /**
     * @param array<string, mixed> $payload
     * @param string[]             $keys
     *
     * @return void
     */
    private function requireKeys(array $payload, array $keys): void
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload) || $payload[$key] === '' || $payload[$key] === NULL) {
                throw new InvalidArgumentException(
                    sprintf('Missing required webhook config field [%s].', $key),
                );
            }
        }
    }

}
