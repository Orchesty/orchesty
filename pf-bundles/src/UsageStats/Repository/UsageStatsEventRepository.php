<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Repository;

use DateTime;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
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
     *
     * @return mixed[]
     */
    public function findBillingEvents(DateTime $startDateTime, int $batchSize): array
    {
        return $this->createQueryBuilder()
            ->field('type')->in([EventTypeEnum::INSTALL->value, EventTypeEnum::UNINSTALL->value])
            ->field('sent')->equals(NULL)
            ->field('created')->lte($startDateTime)
            ->sort('created')
            ->limit($batchSize)
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

}
