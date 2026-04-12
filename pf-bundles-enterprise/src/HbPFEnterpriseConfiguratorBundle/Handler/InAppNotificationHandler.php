<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/**
 * Class InAppNotificationHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class InAppNotificationHandler
{

    private Collection $collection;

    /**
     * InAppNotificationHandler constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(
        DocumentManager $dm,
    )
    {
        $this->collection = $dm->getClient()->selectDatabase(
            $dm->getConfiguration()->getDefaultDB() ?? 'pipes',
        )->selectCollection('notifications');
    }

    /**
     * @param array<string, string|null> $filters
     * @param int                        $page
     * @param int                        $limit
     *
     * @return array<string, mixed>
     */
    public function list(array $filters, int $page, int $limit): array
    {
        $filter = [];

        if (!empty($filters['severity'])) {
            $filter['severity'] = $filters['severity'];
        }

        if (!empty($filters['from']) || !empty($filters['to'])) {
            $filter['createdAt'] = [];
            if (!empty($filters['from'])) {
                $filter['createdAt']['$gte'] = new UTCDateTime(new DateTime($filters['from']));
            }
            if (!empty($filters['to'])) {
                $filter['createdAt']['$lte'] = new UTCDateTime(new DateTime($filters['to']));
            }
        }

        $skip  = ($page - 1) * $limit;
        $total = $this->collection->countDocuments($filter);

        $cursor = $this->collection->find(
            $filter,
            [
                'sort'  => ['createdAt' => -1],
                'skip'  => $skip,
                'limit' => $limit,
            ],
        );

        $data = [];
        foreach ($cursor as $doc) {
            $data[] = $this->normalizeDocument($doc);
        }

        return [
            'data'  => $data,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @param string|null $since
     *
     * @return int
     */
    public function countSince(?string $since): int
    {
        $filter = [];

        if ($since !== NULL) {
            $filter['createdAt'] = [
                '$gt' => new UTCDateTime(new DateTime($since)),
            ];
        }

        return $this->collection->countDocuments($filter);
    }

    /**
     * @param array<string, mixed> $doc
     *
     * @return array<string, mixed>
     */
    private function normalizeDocument(array $doc): array
    {
        return [
            'id'            => (string) ($doc['_id'] ?? ''),
            'tenant_id'     => $doc['tenantId'] ?? '',
            'event_type'    => $doc['eventType'] ?? '',
            'severity'      => $doc['severity'] ?? '',
            'message'       => $doc['message'] ?? '',
            'topology_id'   => $doc['topologyId'] ?? NULL,
            'topology_name' => $doc['topologyName'] ?? NULL,
            'node_name'     => $doc['nodeName'] ?? NULL,
            'created_at'    => isset($doc['createdAt']) ? $doc['createdAt']->toDateTime()->format('c') : NULL,
        ];
    }

}
