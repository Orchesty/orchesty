<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service;

use DateTimeImmutable;

/**
 * Class QuotaCheckResult
 *
 * Outcome of a single `TraceQuotaService::incrementOrReject` decision.
 *
 * `rejected = true` means the increment was NOT persisted because the daily
 * cap was already reached; the caller must surface a `QuotaExceededException`
 * and skip dispatch. When `rejected = false`, `used` is the value AFTER the
 * successful increment.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service
 */
final class QuotaCheckResult
{

    /**
     * QuotaCheckResult constructor.
     *
     * @param bool              $rejected
     * @param int               $used
     * @param int               $limit
     * @param DateTimeImmutable $resetAt
     */
    public function __construct(
        public readonly bool $rejected,
        public readonly int $used,
        public readonly int $limit,
        public readonly DateTimeImmutable $resetAt,
    )
    {
    }

}
