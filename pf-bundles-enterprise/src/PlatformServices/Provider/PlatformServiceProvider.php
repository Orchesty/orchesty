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
 * binding present) or to the cloud-relay default LLM (Trace-only fallback
 * for instances that have Trace enabled and no user binding).
 *
 * Trace cloud-relay path:
 *   1. Per-instance daily quota counter is incremented atomically; if cap
 *      reached, throws `QuotaExceededException` and skips dispatch.
 *   2. `TraceCloudRelayClient` POSTs to the cloud backend, which proxies
 *      to the system-instance `cloud-trace-llm-worker`.
 *   3. On dispatch failure (network, upstream 5xx) the increment is
 *      reverted so transient errors do not eat the user's daily budget.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider
 */
final class PlatformServiceProvider
{

    public const string TRACE_AI_PROVIDER = 'trace-ai-provider';

    /**
     * Returned by `getTraceProviderMode()` for the UI badge / banner.
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
     *   - Binding present              → existing local-SDK dispatch.
     *   - No binding + serviceType is
     *     trace-ai-provider + Trace
     *     feature on + cloud relay
     *     configured                   → cloud-relay default LLM (with cap).
     *   - Otherwise                    → BINDING_NOT_FOUND error.
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

        if ($this->isCloudRelayEligible($serviceType)) {
            return $this->dispatchCloudRelay($method, $data);
        }

        throw new PlatformServiceException(
            sprintf('No application configured for platform service "%s"', $serviceType),
            PlatformServiceException::BINDING_NOT_FOUND,
        );
    }

    /**
     * @param string $serviceType
     *
     * @return bool
     */
    public function isConfigured(string $serviceType): bool
    {
        if ($this->bindingRepository->findOneBy([ServiceBinding::SERVICE_TYPE => $serviceType]) !== NULL) {
            return TRUE;
        }

        return $this->isCloudRelayEligible($serviceType);
    }

    /**
     * Compute the UI mode for the trace-ai-provider service. Used by the
     * `/quota` endpoint to drive the Settings/TraceTab banner.
     *
     * @return string one of MODE_USER / MODE_SYSTEM / MODE_DISABLED
     */
    public function getTraceProviderMode(): string
    {
        $binding = $this->bindingRepository->findOneBy(
            [ServiceBinding::SERVICE_TYPE => self::TRACE_AI_PROVIDER],
        );

        if ($binding !== NULL) {
            return self::MODE_USER;
        }

        if ($this->isCloudRelayEligible(self::TRACE_AI_PROVIDER)) {
            return self::MODE_SYSTEM;
        }

        return self::MODE_DISABLED;
    }

    /**
     * @param string $serviceType
     *
     * @return bool
     */
    private function isCloudRelayEligible(string $serviceType): bool
    {
        return $serviceType === self::TRACE_AI_PROVIDER
            && $this->featureTraceAuditing
            && $this->cloudRelayClient->isConfigured();
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
