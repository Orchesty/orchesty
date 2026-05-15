<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Service;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\Utils\String\Json;
use Throwable;

/**
 * Class HandoffConsumeClient
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Service
 *
 * Client for the cloud's `POST /api/internal/handoff/consume` endpoint.
 *
 * Why a dedicated client (instead of inlining the call in the controller):
 *   - Single place for cloud URL + credential composition.
 *   - Returns a structured outcome so the controller can decide the exact
 *     HTTP response to the user-agent without parsing transport-level
 *     errors.
 *   - Easier to mock in functional tests.
 *
 * Authentication: the consume endpoint expects this instance's id and
 * shared `instanceSecret` in `X-Instance-Id` / `X-Instance-Secret` headers
 * (mirroring the trace-relay endpoint). The secret is the same value that
 * was used to HMAC the token in the first place, so possession is proof
 * the caller is the legitimate target instance.
 */
final class HandoffConsumeClient
{

    public const string OUTCOME_CONSUMED          = 'consumed';
    public const string OUTCOME_NOT_FOUND         = 'not_found';
    public const string OUTCOME_REPLAY_REJECTED   = 'replay_rejected';
    public const string OUTCOME_EXPIRED           = 'expired';
    public const string OUTCOME_AUDIENCE_MISMATCH = 'audience_mismatch';
    public const string OUTCOME_UNREACHABLE       = 'unreachable';
    public const string OUTCOME_NOT_CONFIGURED    = 'not_configured';

    /**
     * HandoffConsumeClient constructor.
     *
     * @param CurlManager $curlManager
     * @param string      $cloudUrl
     * @param string      $instanceId
     * @param string      $instanceSecret
     */
    public function __construct(
        private readonly CurlManager $curlManager,
        private readonly string $cloudUrl,
        private readonly string $instanceId,
        private readonly string $instanceSecret,
    )
    {
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->cloudUrl !== '' && $this->instanceId !== '' && $this->instanceSecret !== '';
    }

    /**
     * Atomically mark the handoff token (identified by `$jti`) as consumed
     * in the cloud. Returns one of the OUTCOME_* constants.
     *
     * On `OUTCOME_UNREACHABLE` the caller MUST decide a policy:
     *   - reject the handoff (strict mode), or
     *   - allow it through and rely on the in-payload TTL + HMAC alone
     *     (degraded mode).
     * The current controller chooses *reject* — see `sessionHandoffAction`.
     *
     * @param string      $jti
     * @param string|null $sourceIp  end-user IP captured at the instance edge
     * @param string|null $userAgent end-user UA captured at the instance edge
     *
     * @return array{outcome: string, message?: string, instanceId?: string|null, userId?: string|null}
     */
    public function consume(string $jti, ?string $sourceIp, ?string $userAgent): array
    {
        if (!$this->isEnabled()) {
            return ['outcome' => self::OUTCOME_NOT_CONFIGURED];
        }

        $url = sprintf('%s/api/internal/handoff/consume', rtrim($this->cloudUrl, '/'));

        $body = Json::encode(array_filter(
            [
                'jti'       => $jti,
                'sourceIp'  => $sourceIp,
                'userAgent' => $userAgent,
            ],
            static fn(mixed $v): bool => $v !== NULL && $v !== '',
        ));

        $dto = new RequestDto(new Uri($url), CurlManager::METHOD_POST, new ProcessDto());
        $dto->setBody($body);
        $dto->setHeaders([
            'Content-Type'       => 'application/json',
            'X-Instance-Id'      => $this->instanceId,
            'X-Instance-Secret'  => $this->instanceSecret,
        ]);

        try {
            $response = $this->curlManager->send($dto);
            $status   = $response->getStatusCode();
            $payload  = $response->getJsonBody();

            if ($status >= 200 && $status < 300) {
                return [
                    'instanceId' => is_string($payload['instanceId'] ?? NULL) ? $payload['instanceId'] : NULL,
                    'outcome'    => self::OUTCOME_CONSUMED,
                    'userId'     => is_string($payload['userId'] ?? NULL) ? $payload['userId'] : NULL,
                ];
            }

            $code = is_string($payload['code'] ?? NULL) ? strtoupper($payload['code']) : '';

            // Defensive: if a foreign service answers on the configured cloud
            // port (very common locally — Grafana grabs :3000), the response
            // will be a 401/403/302 with NO `code` field. We surface a
            // dedicated "wrong upstream" message instead of the generic
            // UNREACHABLE, so the loop guard on the FE shows something
            // actionable in DevTools instead of just "service unavailable".
            if ($code === '' && ($status === 401 || $status === 403 || $status === 302 || $status === 404)) {
                $messageId = is_string($payload['messageId'] ?? NULL) ? $payload['messageId'] : '';
                $hint      = $messageId !== ''
                    ? sprintf('HTTP %d (foreign messageId="%s" — wrong upstream on %s?)', $status, $messageId, $url)
                    : sprintf('HTTP %d (no `code` in body — likely wrong upstream on %s)', $status, $url);

                return ['outcome' => self::OUTCOME_UNREACHABLE, 'message' => $hint];
            }

            return match ($code) {
                'NOT_FOUND'         => ['outcome' => self::OUTCOME_NOT_FOUND, 'message' => $payload['message'] ?? 'Not found'],
                'REPLAY_REJECTED'   => ['outcome' => self::OUTCOME_REPLAY_REJECTED, 'message' => $payload['message'] ?? 'Already consumed'],
                'EXPIRED'           => ['outcome' => self::OUTCOME_EXPIRED, 'message' => $payload['message'] ?? 'Expired'],
                'AUDIENCE_MISMATCH' => ['outcome' => self::OUTCOME_AUDIENCE_MISMATCH, 'message' => $payload['message'] ?? 'Audience mismatch'],
                default             => [
                    'message' => sprintf('HTTP %d', $status),
                    'outcome' => self::OUTCOME_UNREACHABLE,
                ],
            };
        } catch (Throwable $t) {
            // Network failure, JSON parse failure, etc. Caller decides
            // strict-vs-degraded.
            return [
                'message' => $t->getMessage(),
                'outcome' => self::OUTCOME_UNREACHABLE,
            ];
        }
    }

}
