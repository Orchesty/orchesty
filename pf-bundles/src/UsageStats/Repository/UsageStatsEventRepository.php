<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Repository;

use DateTime;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;

/**
 * Class UsageStatsEventRepository
 *
 * @package         Hanaboso\PipesFramework\UsageStats\Repository
 *
 * @phpstan-extends DocumentRepository<UsageStatsEvent>
 */
final class UsageStatsEventRepository extends DocumentRepository
{

    /**
     * @param DateTime $startDateTime
     * @param int      $batchSize
     * @param mixed[]  $types
     *
     * @return mixed[]
     */
    public function findBillingEventsByTypesForSender(DateTime $startDateTime, int $batchSize, array $types): array
    {
        return $this->createQueryBuilder()
            ->field('type')->in($types)
            ->field('sent')->equals(NULL)
            ->field('created')->lte($startDateTime)
            ->sort('created')
            ->limit($batchSize)
            ->getQuery()
            ->toArray();
    }

    /**
     * @param mixed[] $types
     *
     * @return mixed[]
     */
    public function findByTypes(array $types): array
    {
        return $this->createQueryBuilder()
            ->field('type')->in($types)
            ->getQuery()
            ->toArray();
    }

    /**
     * @param DateTime $startDateTime
     *
     * @return mixed
     * @throws MongoDBException
     */
    public function getRemainingEventCount(DateTime $startDateTime): mixed
    {
        return $this->createQueryBuilder()
            ->field('sent')->equals(NULL)
            ->field('type')->in([EventTypeEnum::INSTALL->value, EventTypeEnum::UNINSTALL->value])
            ->field('created')->lte($startDateTime)
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * @param mixed[] $days
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getExistingProcessCountByDay(array $days): array
    {
        $returnMap = [];
        $result    = $this->createAggregationBuilder()
            ->match()
            ->field('type')->equals(EventTypeEnum::OPERATION->value)
            ->field('data.day')->in($days)
            ->group()
            ->field('id')
            ->expression('$data.day')
            ->field('totalSum')
            ->sum('$data.total')
            ->getAggregation()
            ->getIterator()
            ->toArray();

        foreach ($result as $item) {
            $returnMap[$item['_id']] = $item['totalSum'];
        }

        return $returnMap;
    }

}
