<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\TraceReport\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFrameworkEnterprise\TraceReport\Document\TraceReport;

/**
 * Class TraceReportRepository
 *
 * @package Hanaboso\PipesFrameworkEnterprise\TraceReport\Repository
 *
 * @extends DocumentRepository<TraceReport>
 */
final class TraceReportRepository extends DocumentRepository
{

    /**
     * @param string $userId
     * @param int    $page
     * @param int    $limit
     *
     * @return array{items: TraceReport[], total: int}
     */
    public function findByUser(string $userId, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder()
            ->field('userId')->equals($userId);

        $total = (clone $qb)->count()->getQuery()->execute();

        $qb->sort('createdAt', -1)
            ->skip(($page - 1) * $limit)
            ->limit($limit);

        /** @var TraceReport[] $items */
        $items = $qb->getQuery()->execute()->toArray(); /** @phpstan-ignore-line */

        return [
            'items' => array_values($items),
            /** @phpstan-ignore cast.int */
            'total' => (int) $total,
        ];
    }

}
