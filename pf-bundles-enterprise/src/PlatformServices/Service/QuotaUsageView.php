<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service;

use DateTimeImmutable;

/**
 * Read-only snapshot of the current Trace cloud-relay quota window.
 * Returned by `TraceQuotaService::getCurrentUsage()` and surfaced by the
 * `GET /platform-services/trace-ai-provider/quota` endpoint.
 *
 * `mode` is computed by the caller (provider) — it depends on whether a user
 * binding exists, not on the counter itself. This struct only carries the
 * counter view.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service
 */
final class QuotaUsageView
{

    /**
     * @param int               $used
     * @param int               $limit
     * @param DateTimeImmutable $resetAt
     */
    public function __construct(
        public readonly int $used,
        public readonly int $limit,
        public readonly DateTimeImmutable $resetAt,
    )
    {
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'used'    => $this->used,
            'limit'   => $this->limit,
            'resetAt' => $this->resetAt->format(DATE_ATOM),
        ];
    }

}
