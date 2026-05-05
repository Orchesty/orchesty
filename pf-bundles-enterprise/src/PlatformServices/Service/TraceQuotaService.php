<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document\TraceQuotaUsage;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\TraceQuotaUsageRepository;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Operation\FindOneAndUpdate;

/**
 * Class TraceQuotaService
 *
 * Per-instance daily quota counter for the Trace cloud-relay default LLM.
 *
 * The instance increments the counter atomically (single MongoDB upsert with
 * `$inc`) BEFORE issuing the relay request. If the new value exceeds the
 * configured cap, the increment is reverted and the call returns a rejected
 * `QuotaCheckResult`. Caller (`PlatformServiceProvider`) translates that into
 * `QuotaExceededException`.
 *
 * Why pre-increment, not post-success: ordering matters at the cap boundary.
 * Pre-increment means the boundary check is atomic w.r.t. concurrent calls —
 * only one of N concurrent requests can reach `count = cap`, the rest are
 * rejected. Post-increment introduces a race window where two concurrent
 * requests at `count = cap-1` both proceed. With per-instance peak rate
 * around 0.008 turns/sec the race is theoretical, but pre-increment is the
 * cheaper correct primitive (one atomic op vs two).
 *
 * If the relay call fails AFTER a successful increment, the counter is
 * decremented again via `revertIncrement()` so a network blip does not eat
 * the user's daily budget.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service
 */
final class TraceQuotaService
{

    /**
     * TraceQuotaService constructor.
     *
     * @param DocumentManager           $dm
     * @param TraceQuotaUsageRepository $repository
     * @param int                       $dailyCap
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly TraceQuotaUsageRepository $repository,
        private readonly int $dailyCap,
    )
    {
    }

    /**
     * Atomic pre-increment + cap check.
     *
     * @return QuotaCheckResult
     *
     * @throws MongoDBException
     */
    public function incrementOrReject(): QuotaCheckResult
    {
        $now         = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $windowStart = $this->utcMidnight($now);
        $resetAt     = $windowStart->modify('+1 day');

        $collection = $this->dm->getDocumentCollection(TraceQuotaUsage::class);

        $result = $collection->findOneAndUpdate(
            [TraceQuotaUsage::WINDOW_START => new UTCDateTime($windowStart)],
            [
                '$inc' => [TraceQuotaUsage::COUNT => 1],
                '$set' => [TraceQuotaUsage::LAST_INCREMENTED_AT => new UTCDateTime($now)],
                '$setOnInsert' => [TraceQuotaUsage::WINDOW_START => new UTCDateTime($windowStart)],
            ],
            [
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                'upsert'         => TRUE,
            ],
        );

        $newCount = is_array($result) ? (int) ($result[TraceQuotaUsage::COUNT] ?? 1) : 1;

        if ($newCount > $this->dailyCap) {
            // Pre-increment exceeded cap; revert so the counter reflects reality.
            $this->revertIncrement($windowStart);

            return new QuotaCheckResult(
                rejected: TRUE,
                used: $this->dailyCap,
                limit: $this->dailyCap,
                resetAt: $resetAt,
            );
        }

        return new QuotaCheckResult(rejected: FALSE, used: $newCount, limit: $this->dailyCap, resetAt: $resetAt);
    }

    /**
     * Refund a previously successful increment when the dispatch failed
     * downstream (network error to cloud-relay, system instance 5xx, etc.).
     *
     * @param DateTimeImmutable|null $windowStart
     *
     * @throws MongoDBException
     */
    public function revertIncrement(?DateTimeImmutable $windowStart = NULL): void
    {
        $windowStart ??= $this->utcMidnight(new DateTimeImmutable('now', new DateTimeZone('UTC')));

        $this->dm->getDocumentCollection(TraceQuotaUsage::class)->updateOne(
            [TraceQuotaUsage::WINDOW_START => new UTCDateTime($windowStart)],
            ['$inc' => [TraceQuotaUsage::COUNT => -1]],
        );
    }

    /**
     * Read-only snapshot for UI badge / banner.
     *
     * @return QuotaUsageView
     */
    public function getCurrentUsage(): QuotaUsageView
    {
        $now         = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $windowStart = $this->utcMidnight($now);
        $resetAt     = $windowStart->modify('+1 day');

        /** @var TraceQuotaUsage|null $doc */
        $doc = $this->repository->findOneBy([
            TraceQuotaUsage::WINDOW_START => $windowStart,
        ]);

        $used = $doc?->getCount() ?? 0;

        return new QuotaUsageView(
            used: max(0, min($used, $this->dailyCap)),
            limit: $this->dailyCap,
            resetAt: $resetAt,
        );
    }

    /**
     * @return int
     */
    public function getDailyCap(): int
    {
        return $this->dailyCap;
    }

    /**
     * @param DateTimeImmutable $now
     *
     * @return DateTimeImmutable
     */
    private function utcMidnight(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->setTime(0, 0, 0, 0);
    }

}
