<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception;

use DateTimeImmutable;

/**
 * Thrown by `PlatformServiceProvider` when the per-instance daily quota for
 * the Trace cloud-relay default LLM is reached. Carries enough context for
 * the trace bridge to translate it into a UI-friendly `quota_exceeded` WS
 * message (limit / used / resetAt).
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception
 */
final class QuotaExceededException extends PlatformServiceException
{

    /**
     * @param int               $limit
     * @param int               $used
     * @param DateTimeImmutable $resetAt
     */
    public function __construct(
        private readonly int $limit,
        private readonly int $used,
        private readonly DateTimeImmutable $resetAt,
    )
    {
        parent::__construct(
            sprintf(
                'Trace daily quota exceeded: %d / %d. Resets at %s.',
                $used,
                $limit,
                $resetAt->format(DATE_ATOM),
            ),
            self::QUOTA_EXCEEDED,
        );
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getUsed(): int
    {
        return $this->used;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getResetAt(): DateTimeImmutable
    {
        return $this->resetAt;
    }

    /**
     * @return mixed[]
     */
    public function toPayload(): array
    {
        return [
            'code'    => 'QUOTA_EXCEEDED',
            'limit'   => $this->limit,
            'used'    => $this->used,
            'resetAt' => $this->resetAt->format(DATE_ATOM),
        ];
    }

}
