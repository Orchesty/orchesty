<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

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
    public function getDataForDashboard(array $range): array {
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

}
