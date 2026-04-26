<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Application\Manager\WebhookConfigManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Endpoints driving the new UI-managed webhook flow.
 *
 * The previously-existing /webhook/applications/{key}/(un)subscribe endpoints
 * stay in place for backward compatibility with topology-driven registration;
 * these new endpoints work with the persisted {@see WebhookConfig} documents.
 */
final class WebhookConfigController extends AbstractController
{

    // phpcs:disable SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing.IncorrectLinesCountBetweenAttributeAndTarget

    public function __construct(
        private readonly WebhookConfigManager $manager,
        private readonly ServiceLocator $locator,
    )
    {
    }

    #[Route('/topologies/by-name/{topologyName}/webhook-configs', methods: ['GET', 'OPTIONS'])]
    public function listAction(string $topologyName): Response
    {
        return new JsonResponse(['items' => $this->manager->listForTopology($topologyName)]);
    }

    /**
     * Upsert the webhook configuration for (topology, node).
     */
    #[Route(
        '/topologies/by-name/{topologyName}/nodes/{nodeName}/webhook-config',
        methods: ['PUT', 'POST', 'OPTIONS'],
    )]
    public function upsertAction(Request $request, string $topologyName, string $nodeName): Response
    {
        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            $payload = $request->request->all();
        }

        $config = $this->manager->upsertConfig($topologyName, $nodeName, $payload);

        return new JsonResponse($config->toArray());
    }

    #[Route(
        '/topologies/by-name/{topologyName}/nodes/{nodeName}/webhook-config',
        methods: ['DELETE', 'OPTIONS'],
    )]
    public function deleteAction(string $topologyName, string $nodeName): Response
    {
        $this->manager->deleteConfig($topologyName, $nodeName);

        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * Subscribe a webhook node by `(topology, node)` only. The underlying
     * {@see WebhookConfig} document is created lazily on first call so the UI
     * can stay free of "config" terminology — the user just clicks Subscribe.
     *
     * Optional JSON body: `{ "parameters": { ... } }` — passed verbatim to the
     * SDK's webhook subscribe call (filters, source, channel, etc.).
     *
     * The endpoint is fully idempotent: a second subscribe against an
     * already-active webhook returns `200` with `payload.noop = true` rather
     * than re-issuing the upstream request.
     */
    #[Route(
        '/topologies/by-name/{topologyName}/nodes/{nodeName}/webhook-config/subscribe',
        methods: ['POST', 'OPTIONS'],
    )]
    public function subscribeAction(Request $request, string $topologyName, string $nodeName): Response
    {
        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            $payload = $request->request->all();
        }

        $parameters = isset($payload['parameters']) && is_array($payload['parameters'])
            ? $payload['parameters']
            : NULL;

        try {
            $result = $this->manager->subscribeForNode($topologyName, $nodeName, $parameters);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $t->getMessage()],
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return new JsonResponse(['status' => 'ok', 'payload' => $result]);
    }

    /**
     * Unsubscribe a webhook node by `(topology, node)`. Idempotent — calling
     * with no existing config or no live registration returns `200` with
     * `payload.noop = true` and never throws 4xx, matching the user-facing
     * intent ("turn this webhook off, regardless of current state").
     */
    #[Route(
        '/topologies/by-name/{topologyName}/nodes/{nodeName}/webhook-config/unsubscribe',
        methods: ['POST', 'OPTIONS'],
    )]
    public function unsubscribeAction(string $topologyName, string $nodeName): Response
    {
        try {
            $result = $this->manager->unsubscribeForNode($topologyName, $nodeName);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $t->getMessage()],
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return new JsonResponse(['status' => 'ok', 'payload' => $result]);
    }

    /**
     * Cascade subscribe / unsubscribe for every webhook config attached to a topology.
     */
    #[Route('/topologies/by-name/{topologyName}/webhook-configs/cascade', methods: ['POST', 'OPTIONS'])]
    public function cascadeAction(Request $request, string $topologyName): Response
    {
        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            $payload = $request->request->all();
        }

        $enable = (bool) ($payload['enable'] ?? FALSE);

        return new JsonResponse([
            'status' => 'ok',
            'items'  => $this->manager->cascadeForTopology($topologyName, $enable),
        ]);
    }

    /**
     * Returns the catalog of webhook events an application supports — used by
     * the UI to populate the event dropdown when configuring a Webhook node.
     */
    #[Route('/applications/{key}/webhook-events', methods: ['GET', 'OPTIONS'])]
    public function listEventsAction(
        Request $request,
        string $key,
        #[MapQueryParameter] string $sdk,
    ): Response
    {
        $request->query->set('user', ApplicationController::SYSTEM_USER);
        $request->query->set('sdk', $sdk);

        return new Response(
            $this->locator->runSyncActions($request, $key, $sdk, 'listWebhookEvents'),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
        );
    }

}
