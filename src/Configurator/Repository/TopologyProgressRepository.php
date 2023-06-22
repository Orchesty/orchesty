<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use DateTime;
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

        return !empty($result) ? $result[0] : ['failed' => 0, 'total' => 0];
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

}
