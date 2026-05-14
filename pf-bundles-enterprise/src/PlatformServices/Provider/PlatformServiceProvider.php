<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document\ServiceBinding;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception\PlatformServiceException;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception\QuotaExceededException;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\ServiceBindingRepository;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service\TraceQuotaService;
use Hanaboso\Utils\String\Json;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class PlatformServiceProvider
 *
 * Dispatches platform-service calls to either the local SDK (user-managed
 * binding present) or to the cloud-relay default LLM (when no binding is
 * configured and the instance has Trace enabled).
 *
 * Access policy — single gate:
 *   Trace UI surface and binding settings are available iff
 *   `ORCHESTY_FEATURE_TRACE_AUDITING=true`. The relay reachability
 *   (`TraceCloudRelayClient::isConfigured()`) is **not** a gate — it only
 *   determines whether the default LLM call physically succeeds at
 *   runtime. Users on instances without a relay can still bring their own
 *   LLM (Settings → Trace).
 *
 * Trace cloud-relay path (when used at runtime):
 *   1. Per-instance daily quota counter is incremented atomically; if cap
 *      reached, throws `QuotaExceededException` and skips dispatch.
 *   2. `TraceCloudRelayClient` POSTs to the cloud backend, which proxies
 *      to the system-instance `cloud-trace-llm-worker`.
 *   3. On dispatch failure (network, upstream 5xx, relay-not-configured)
 *      the increment is reverted so transient errors do not eat the
 *      user's daily budget.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider
 */
final class PlatformServiceProvider
{

    public const string TRACE_AI_PROVIDER = 'trace-ai-provider';

    /**
     * Returned by `getTraceProviderMode()` for the UI badge / banner.
     *
     * Semantics:
     *   - MODE_DISABLED — `featureTraceAuditing=false`. UI hides the tab.
     *   - MODE_USER     — Feature on AND a user binding exists.
     *   - MODE_SYSTEM   — Feature on AND no user binding (relay default
     *                     LLM may apply at runtime; UI shows binding
     *                     editor either way).
     */
    public const string MODE_USER     = 'user';
    public const string MODE_SYSTEM   = 'system';
    public const string MODE_DISABLED = 'disabled';

    /**
     * PlatformServiceProvider constructor.
     *
     * @param DocumentManager          $dm
     * @param ServiceBindingRepository $bindingRepository
     * @param ServiceLocator           $serviceLocator
     * @param TraceQuotaService        $traceQuotaService
     * @param TraceCloudRelayClient    $cloudRelayClient
     * @param bool                     $featureTraceAuditing
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly ServiceBindingRepository $bindingRepository,
        private readonly ServiceLocator $serviceLocator,
        private readonly TraceQuotaService $traceQuotaService,
        private readonly TraceCloudRelayClient $cloudRelayClient,
        private readonly bool $featureTraceAuditing,
    )
    {
    }

    /**
     * Call a sync action on the platform-bound application.
     *
     * Routing:
     *   - Binding present                  → local-SDK dispatch.
     *   - No binding + serviceType is
     *     trace-ai-provider + Trace
     *     feature on                       → cloud-relay default LLM
     *                                        (with daily cap). Relay
     *                                        reachability is checked
     *                                        inside `dispatchCloudRelay`
     *                                        and surfaces as
     *                                        `RELAY_FAILED` if missing.
     *   - Otherwise                        → BINDING_NOT_FOUND error.
     *
     * @param string  $serviceType
     * @param string  $method
     * @param mixed[] $data
     *
     * @return mixed[]
     *
     * @throws PlatformServiceException
     * @throws QuotaExceededException
     */
    public function call(string $serviceType, string $method, array $data = []): array
    {
        /** @var ServiceBinding|null $binding */
        $binding = $this->bindingRepository->findOneBy(
            [ServiceBinding::SERVICE_TYPE => $serviceType],
        );

        if ($binding !== NULL) {
            return $this->dispatchLocal($binding, $method, $data);
        }

        if ($serviceType === self::TRACE_AI_PROVIDER && $this->featureTraceAuditing) {
            return $this->dispatchCloudRelay($method, $data);
        }

        throw new PlatformServiceException(
            sprintf('No application configured for platform service "%s"', $serviceType),
            PlatformServiceException::BINDING_NOT_FOUND,
        );
    }

    /**
     * Whether the platform service can serve a call.
     *
     * For Trace, a configured service means either a user binding exists
     * or the feature flag is on (default-LLM path is attempted at
     * runtime; relay reachability is a runtime detail, not a gate).
     *
     * @param string $serviceType
     *
     * @return bool
     */
    public function isConfigured(string $serviceType): bool
    {
        if ($this->bindingRepository->findOneBy([ServiceBinding::SERVICE_TYPE => $serviceType]) !== NULL) {
            return TRUE;
        }

        return $serviceType === self::TRACE_AI_PROVIDER && $this->featureTraceAuditing;
    }

    /**
     * Compute the UI mode for the trace-ai-provider service. Used by the
     * `/quota` endpoint to drive the Settings/TraceTab banner.
     *
     * Single gate is `featureTraceAuditing`. Cloud-relay reachability is
     * intentionally NOT consulted here — instances without a relay can
     * still bring their own LLM via the binding editor.
     *
     * @return string one of MODE_USER / MODE_SYSTEM / MODE_DISABLED
     */
    public function getTraceProviderMode(): string
    {
        if (!$this->featureTraceAuditing) {
            return self::MODE_DISABLED;
        }

        $binding = $this->bindingRepository->findOneBy(
            [ServiceBinding::SERVICE_TYPE => self::TRACE_AI_PROVIDER],
        );

        return $binding !== NULL ? self::MODE_USER : self::MODE_SYSTEM;
    }

    /**
     * @param string  $method
     * @param mixed[] $data
     *
     * @return mixed[]
     *
     * @throws MongoDBException
     * @throws PlatformServiceException
     * @throws QuotaExceededException
     */
    private function dispatchCloudRelay(string $method, array $data): array
    {
        $check = $this->traceQuotaService->incrementOrReject();
        if ($check->rejected) {
            throw new QuotaExceededException(limit: $check->limit, used: $check->used, resetAt: $check->resetAt);
        }

        // The cloud-relay endpoint expects no SDK / user fields (no local
        // SDK in the loop), but we keep the same shape so the system-instance
        // worker handler can reuse the existing `syncTrace` signature.
        $payload = $data;
        unset($payload['sdk']);

        try {
            return $this->cloudRelayClient->dispatch($method, $payload);
        } catch (QuotaExceededException $e) {
            // Defensive limit on cloud-backend: counter was already
            // incremented for this turn, refund it so the user is not
            // double-charged in their daily window.
            $this->traceQuotaService->revertIncrement();

            throw $e;
        } catch (Throwable $e) {
            // Network / upstream failure: refund the counter so a transient
            // outage does not eat from the daily budget.
            $this->traceQuotaService->revertIncrement();

            if ($e instanceof PlatformServiceException) {
                throw $e;
            }

            throw new PlatformServiceException(
                sprintf('Trace cloud-relay dispatch failed: %s', $e->getMessage()),
                PlatformServiceException::RELAY_FAILED,
                $e,
            );
        }
    }

    /**
     * @param ServiceBinding $binding
     * @param string         $method
     * @param mixed[]        $data
     *
     * @return mixed[]
     *
     * @throws PlatformServiceException
     */
    private function dispatchLocal(ServiceBinding $binding, string $method, array $data): array
    {
        $appKey = $binding->getApplicationKey();
        $sdk    = $this->resolveSdk($binding);

        try {
            $data['sdk'] = $sdk;

            // Platform services are system-wide: applications they call were installed via the
            // admin UI under ApplicationController::SYSTEM_USER. The caller's user (e.g. the chat
            // session owner) is irrelevant for the ApplicationInstall lookup on the worker, so we
            // override it here. The binding's `user` field stays as audit metadata only.
            $data['user'] = ApplicationController::SYSTEM_USER;

            $request  = new Request([], $data, [], [], [], ['REQUEST_METHOD' => 'POST']);
            $response = $this->serviceLocator->runSyncActions($request, $appKey, $sdk, $method);

            return Json::decode($response);
        } catch (Throwable $e) {
            throw new PlatformServiceException(
                sprintf(
                    'Platform service call failed [%s/%s]: %s',
                    $binding->getServiceType(),
                    $method,
                    $e->getMessage(),
                ),
                PlatformServiceException::CALL_FAILED,
                $e,
            );
        }
    }

    /**
     * Resolve the SDK name for a binding.
     *
     * - If the binding has no sdk yet (legacy record), auto-discover one and persist it back.
     * - If the binding has an sdk but the application is no longer installed there, fail hard.
     *
     * @param ServiceBinding $binding
     *
     * @return string
     *
     * @throws PlatformServiceException
     */
    private function resolveSdk(ServiceBinding $binding): string
    {
        $appKey = $binding->getApplicationKey();
        $sdk    = $binding->getSdk();

        if ($sdk === NULL || $sdk === '') {
            $sdk = $this->serviceLocator->getSdkNameByInstalledApplication($appKey);
            $binding->setSdk($sdk);

            try {
                $this->dm->flush();
            } catch (MongoDBException) {
                // Backfill is best-effort; even if persistence fails the call below still works.
            }

            return $sdk;
        }

        if (!$this->serviceLocator->isApplicationInstalledOnSdk($appKey, $sdk)) {
            throw new PlatformServiceException(
                sprintf(
                    'Application "%s" is no longer installed on worker "%s" for platform service "%s". Update the binding in Settings.',
                    $appKey,
                    $sdk,
                    $binding->getServiceType(),
                ),
                PlatformServiceException::CALL_FAILED,
            );
        }

        return $sdk;
    }

}
