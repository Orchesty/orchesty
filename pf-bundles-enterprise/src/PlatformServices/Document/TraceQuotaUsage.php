<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class TraceQuotaUsage
 *
 * Per-day trace cloud-relay usage counter.
 *
 * One document per UTC day. The instance increments `count` atomically before
 * dispatching a Trace request through the cloud-relay default LLM. When `count`
 * would exceed the configured daily cap, the instance rejects the request with
 * `QuotaExceededException` and never makes the relay call.
 *
 * The TTL index on `windowStart` cleans up history after 7 days so the
 * collection stays bounded without explicit retention jobs.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document
 */
#[ODM\Document(
    repositoryClass: 'Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\TraceQuotaUsageRepository',
    collection: 'trace_quota_usage',
)]
#[ODM\UniqueIndex(keys: ['windowStart' => 'asc'])]
#[ODM\Index(keys: ['windowStart' => 'asc'], options: ['expireAfterSeconds' => 604_800])]
final class TraceQuotaUsage
{

    use IdTrait;

    public const string WINDOW_START        = 'windowStart';
    public const string COUNT               = 'count';
    public const string LAST_INCREMENTED_AT = 'lastIncrementedAt';

    /**
     * @var DateTimeImmutable
     */
    #[ODM\Field(type: 'date_immutable')]
    private DateTimeImmutable $windowStart;

    /**
     * @var int
     */
    #[ODM\Field(type: 'int')]
    private int $count = 0;

    /**
     * @var DateTimeImmutable|null
     */
    #[ODM\Field(type: 'date_immutable', nullable: TRUE)]
    private ?DateTimeImmutable $lastIncrementedAt = NULL;

    /**
     * @return DateTimeImmutable
     */
    public function getWindowStart(): DateTimeImmutable
    {
        return $this->windowStart;
    }

    /**
     * @param DateTimeImmutable $windowStart
     *
     * @return TraceQuotaUsage
     */
    public function setWindowStart(DateTimeImmutable $windowStart): self
    {
        $this->windowStart = $windowStart;

        return $this;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     *
     * @return TraceQuotaUsage
     */
    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getLastIncrementedAt(): ?DateTimeImmutable
    {
        return $this->lastIncrementedAt;
    }

    /**
     * @param DateTimeImmutable|null $lastIncrementedAt
     *
     * @return TraceQuotaUsage
     */
    public function setLastIncrementedAt(?DateTimeImmutable $lastIncrementedAt): self
    {
        $this->lastIncrementedAt = $lastIncrementedAt;

        return $this;
    }

}
