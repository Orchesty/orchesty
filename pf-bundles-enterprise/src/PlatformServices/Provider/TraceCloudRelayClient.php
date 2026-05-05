<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception\PlatformServiceException;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception\QuotaExceededException;
use Hanaboso\Utils\String\Json;
use Throwable;

/**
 * HTTP client for the Trace cloud-relay default LLM proxy.
 *
 * When a user instance has Trace enabled but no user-managed
 * `trace-ai-provider` binding, `PlatformServiceProvider` dispatches the LLM
 * request through this client instead of the local `ServiceLocator`. The
 * cloud-backend hosts the dispatch endpoint, applies a defensive abuse
 * rate-limit, and forwards to the system instance API gateway, which in
 * turn routes to `cloud-trace-llm-worker`.
 *
 * Authentication mirrors the existing instance->cloud callbacks pattern
 * (`X-Instance-Id` / `X-Instance-Secret`), so no new credential plumbing
 * is required.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Provider
 */
final class TraceCloudRelayClient
{

    private const string PATH    = '/api/internal/trace-relay/chat';
    private const int TIMEOUT_MS = 60_000;

    /**
     * @param CurlManager $curlManager
     * @param string      $cloudBackendUrl
     * @param string      $instanceId
     * @param string      $instanceSecret
     */
    public function __construct(
        private readonly CurlManager $curlManager,
        private readonly string $cloudBackendUrl,
        private readonly string $instanceId,
        private readonly string $instanceSecret,
    )
    {
    }

    /**
     * Whether this instance is configured to call the cloud relay.
     * False on on-prem deployments where instance lives outside Orchesty Cloud.
     */
    public function isConfigured(): bool
    {
        return $this->cloudBackendUrl !== ''
            && $this->instanceId !== ''
            && $this->instanceSecret !== '';
    }

    /**
     * Forward a Trace AI provider call to the cloud backend.
     *
     * @param string  $method  Sync action method on the remote Application
     *                         (e.g. `syncTrace`).
     * @param mixed[] $payload Original platform-service call payload (already
     *                         enriched with `sdk` / `user` keys by the
     *                         caller).
     *
     * @return mixed[]
     *
     * @throws PlatformServiceException
     * @throws QuotaExceededException
     */
    public function dispatch(string $method, array $payload): array
    {
        if (!$this->isConfigured()) {
            throw new PlatformServiceException(
                'Trace cloud-relay is not configured (missing ORCHESTY_CLOUD_BACKEND_URL / instance creds).',
                PlatformServiceException::RELAY_FAILED,
            );
        }

        $url = sprintf('%s%s', rtrim($this->cloudBackendUrl, '/'), self::PATH);

        $body = Json::encode([
            'method'  => $method,
            'payload' => $payload,
        ]);

        $dto = new RequestDto(new Uri($url), CurlManager::METHOD_POST, new ProcessDto());
        $dto->setBody($body);
        $dto->setHeaders([
            'Content-Type'      => 'application/json',
            'X-Instance-Id'     => $this->instanceId,
            'X-Instance-Secret' => $this->instanceSecret,
        ]);
        $dto->setTimeout(self::TIMEOUT_MS);

        try {
            $response = $this->curlManager->send($dto);
        } catch (Throwable $e) {
            throw new PlatformServiceException(
                sprintf('Trace cloud-relay request failed: %s', $e->getMessage()),
                PlatformServiceException::RELAY_FAILED,
                $e,
            );
        }

        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return $response->getJsonBody();
        }

        if ($status === 429) {
            // Defensive rate-limit hit on cloud-backend (per-instance abuse
            // guard). Translate to QuotaExceededException so the trace bridge
            // funnels it through the same `quota_exceeded` WS message as the
            // primary user-quota path; payload preserves the underlying code
            // so support can distinguish in logs.
            $body = $this->safeJsonBody($response->getBody());
            $resetAt = $this->parseResetAt($body);

            throw new QuotaExceededException(
                limit: (int) ($body['limit'] ?? 0),
                used: (int) ($body['used'] ?? 0),
                resetAt: $resetAt,
            );
        }

        throw new PlatformServiceException(
            sprintf('Trace cloud-relay upstream returned %d', $status),
            PlatformServiceException::RELAY_FAILED,
        );
    }

    /**
     * @param string $raw
     *
     * @return mixed[]
     */
    private function safeJsonBody(string $raw): array
    {
        try {
            $decoded = Json::decode($raw);

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param mixed[] $body
     *
     * @return DateTimeImmutable
     */
    private function parseResetAt(array $body): DateTimeImmutable
    {
        $raw = $body['resetAt'] ?? null;
        if (is_string($raw) && $raw !== '') {
            try {
                return new DateTimeImmutable($raw);
            } catch (Throwable) {
                // fallthrough
            }
        }

        // Defensive limit responses include `retryAfterSec`; project that
        // forward instead of guessing the next UTC midnight.
        $retryAfter = $body['retryAfterSec'] ?? null;
        if (is_int($retryAfter) || (is_string($retryAfter) && ctype_digit($retryAfter))) {
            return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->modify(sprintf('+%d seconds', (int) $retryAfter));
        }

        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setTime(0, 0, 0, 0)
            ->modify('+1 day');
    }

}
