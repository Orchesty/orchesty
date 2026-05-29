<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use DateTime;
use DateTimeInterface;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class TopologyProgressRepository
 *
 * @package Hanaboso\PipesFramework\Configurator\Repository
 *
 * @phpstan-extends DocumentRepository<TopologyProgress>
 */
final class TopologyProgressRepository extends DocumentRepository
{

    /**
     * @param mixed[] $range
     *
     * @return mixed[]
     * @throws DateTimeException
     * @throws Exception
     */
    public function getDataForDashboard(array $range): array
    {
        $ab = $this->createAggregationBuilder();

        $result = $ab
            ->match()
            ->field('created')
            ->gte(DateTimeUtils::getUtcDateTime($range['from']))
            ->lt(DateTimeUtils::getUtcDateTime($range['to']))
            ->project()
            ->field('failed')->cond($ab->expr()->gt('$nok', 0), 1, 0)
            ->group()
            ->field('id')->expression(NULL)
            ->field('failed')->sum('$failed')
            ->field('total')->sum(1)
            ->getAggregation()
            ->getIterator()
            ->toArray();

        return $result !== [] ? $result[0] : ['failed' => 0, 'total' => 0];
    }

    /**
     * @param DateTime $lastRun
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getDataForOperationEventSending(DateTime $lastRun): array
    {
        $ab = $this->createAggregationBuilder();
        $lastRun->setTime(0, 0);
        $endDate = new DateTime();
        $endDate->setTime(0, 0);

        $firstAggregationResult = $ab
            ->match()
            ->field('finished')
            ->gte(DateTimeUtils::getUtcDateTime($lastRun->format(DateTimeUtils::DATE_TIME_UTC)))
            ->lt(DateTimeUtils::getUtcDateTime($endDate->format(DateTimeUtils::DATE_TIME_UTC)))
            ->group()
            ->field('_id')
            ->expression($ab->expr()->dateToString('%Y-%m-%d', '$created'))
            ->getAggregation()->getIterator()->toArray();

        $dates = array_map(static fn($item): string => $item['_id'], $firstAggregationResult);

        $ab = $this->createAggregationBuilder();

        return $ab
            ->project()
            ->field('createdDay')
            ->dateToString('%Y-%m-%d', '$created')
            ->field('processedCount')->expression('$processedCount')
            ->match()
            ->field('createdDay')
            ->in($dates)
            ->group()
            ->field('id')
            ->expression('$createdDay')
            ->field('total')->sum('$processedCount')
            ->getAggregation()->getIterator()->toArray();
    }

    /**
     * Aggregates per-topology activity in a time window.
     *
     * Each result row carries the topologyId (in `_id`), the run count and
     * the number of runs that finished successfully, finished with at least
     * one failed step (`nok > 0`) or are still in flight (no `finished`
     * timestamp yet). The youngest and oldest run timestamps are also
     * surfaced so the renderer can show "last seen at …".
     *
     * Backed by `IK_multiCounter_created_topologyId_nok_finished`, so the
     * Mongo planner can satisfy this entirely from the index without a
     * collection scan.
     *
     * @param DateTimeInterface      $from start of the window (inclusive)
     * @param DateTimeInterface|null $to   end of the window (exclusive); null leaves it open-ended
     *
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function getActivityByTopology(DateTimeInterface $from, ?DateTimeInterface $to): array
    {
        $ab = $this->createAggregationBuilder();

        $match = $ab->match()->field('created')->gte(
            DateTimeUtils::getUtcDateTime($from->format(DateTimeUtils::DATE_TIME_UTC)),
        );
        if ($to !== NULL) {
            $match->field('created')->lt(DateTimeUtils::getUtcDateTime($to->format(DateTimeUtils::DATE_TIME_UTC)));
        }

        return $ab
            ->group()
                ->field('id')->expression('$topologyId')
                ->field('runs')->sum(1)
                ->field('success')->sum(
                    $ab->expr()->cond(
                        $ab->expr()->and(
                            $ab->expr()->ne('$finished', NULL),
                            $ab->expr()->eq('$nok', 0),
                        ),
                        1,
                        0,
                    ),
                )
                ->field('failed')->sum(
                    $ab->expr()->cond(
                        $ab->expr()->gt('$nok', 0),
                        1,
                        0,
                    ),
                )
                ->field('running')->sum(
                    $ab->expr()->cond(
                        $ab->expr()->eq('$finished', NULL),
                        1,
                        0,
                    ),
                )
                ->field('lastRunAt')->max('$created')
                ->field('firstRunAt')->min('$created')
            ->sort(['runs' => -1, 'lastRunAt' => -1])
            ->getAggregation()->getIterator()->toArray();
    }

}
