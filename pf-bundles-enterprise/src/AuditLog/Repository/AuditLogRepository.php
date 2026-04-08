<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\AuditLog\Repository;

use DateTime;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFrameworkEnterprise\AuditLog\Document\AuditLog;
use MongoDB\BSON\Regex;

/**
 * Class AuditLogRepository
 *
 * @package Hanaboso\PipesFrameworkEnterprise\AuditLog\Repository
 *
 * @extends DocumentRepository<AuditLog>
 */
final class AuditLogRepository extends DocumentRepository
{

    /**
     * @param string|null $search
     * @param string|null $action
     * @param string|null $resource
     * @param string|null $from
     * @param string|null $to
     * @param string      $sortField
     * @param string      $sortDir
     * @param int         $page
     * @param int         $limit
     *
     * @return array{items: AuditLog[], total: int}
     */
    public function findFiltered(
        ?string $search = NULL,
        ?string $action = NULL,
        ?string $resource = NULL,
        ?string $from = NULL,
        ?string $to = NULL,
        string $sortField = 'timestamp',
        string $sortDir = 'desc',
        int $page = 1,
        int $limit = 20,
    ): array
    {
        $qb = $this->createQueryBuilder();

        if ($search !== NULL && $search !== '') {
            $regex = preg_quote($search, '/');
            $qb->addOr(
                $qb->expr()->field('userEmail')->equals(new Regex($regex, 'i')),
                $qb->expr()->field('resourceName')->equals(new Regex($regex, 'i')),
            );
        }

        if ($action !== NULL && $action !== '') {
            $qb->field('action')->equals($action);
        }

        if ($resource !== NULL && $resource !== '') {
            $qb->field('resource')->equals($resource);
        }

        if ($from !== NULL && $from !== '') {
            $qb->field('timestamp')->gte(new DateTime($from));
        }

        if ($to !== NULL && $to !== '') {
            $qb->field('timestamp')->lte(new DateTime($to));
        }

        $total = (clone $qb)->count()->getQuery()->execute();

        $sortDirection = strtolower($sortDir) === 'asc' ? 1 : -1;
        $allowedSort   = ['timestamp', 'userEmail', 'action', 'resource', 'statusCode'];
        $sortBy        = in_array($sortField, $allowedSort, TRUE) ? $sortField : 'timestamp';

        $qb->sort($sortBy, $sortDirection)
            ->skip(($page - 1) * $limit)
            ->limit($limit);

        /** @var AuditLog[] $items */
        $items = $qb->getQuery()->execute()->toArray(); /** @phpstan-ignore-line */

        return [
            'items' => array_values($items),
            /** @phpstan-ignore cast.int */
            'total' => (int) $total,
        ];
    }

}
