<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Handler;

use Hanaboso\PipesFramework\UsageStats\Event\BillingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UsageStatsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\Handler
 */
final class UsageStatsHandler
{

    /**
     * UsageStatsHandler constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * @param mixed[] $body
     *
     * @return mixed[]
     */
    public function emitEvent(array $body): array
    {
        if (isset($body['event']) && isset($body['aid']) && isset($body['euid'])) {
            $billingEvent = new BillingEvent($body['event'], ['aid' => $body['aid'], 'euid' => $body['euid']]);
            $this->eventDispatcher->dispatch($billingEvent, BillingEvent::NAME);
        }

        return [];
    }

}
