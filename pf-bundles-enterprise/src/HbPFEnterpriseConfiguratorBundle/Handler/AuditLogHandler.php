<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Hanaboso\PipesFrameworkEnterprise\AuditLog\Document\AuditLog;
use Hanaboso\PipesFrameworkEnterprise\AuditLog\Repository\AuditLogRepository;
use InvalidArgumentException;

/**
 * Class AuditLogHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class AuditLogHandler
{

    /**
     * AuditLogHandler constructor.
     *
     * @param AuditLogRepository $repository
     */
    public function __construct(
        private readonly AuditLogRepository $repository,
    )
    {
    }

    /**
     * @param string|null $search
     * @param string|null $action
     * @param string|null $resource
     * @param string|null $from
     * @param string|null $to
     * @param string      $sort
     * @param string      $order
     * @param int         $page
     * @param int         $limit
     *
     * @return mixed[]
     */
    public function getAuditLogs(
        ?string $search = NULL,
        ?string $action = NULL,
        ?string $resource = NULL,
        ?string $from = NULL,
        ?string $to = NULL,
        string $sort = 'timestamp',
        string $order = 'desc',
        int $page = 1,
        int $limit = 20,
    ): array
    {
        $result = $this->repository->findFiltered(
            $search,
            $action,
            $resource,
            $from,
            $to,
            $sort,
            $order,
            $page,
            $limit,
        );

        $items = array_map(
            static fn(AuditLog $log): array => $log->toArray(),
            $result['items'],
        );

        return [
            'items' => $items,
            'total' => $result['total'],
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     */
    public function getAuditLog(string $id): array
    {
        $log = $this->repository->find($id);

        if (!$log instanceof AuditLog) {
            throw new InvalidArgumentException(sprintf('Audit log [%s] not found.', $id));
        }

        return $log->toArray();
    }

}
