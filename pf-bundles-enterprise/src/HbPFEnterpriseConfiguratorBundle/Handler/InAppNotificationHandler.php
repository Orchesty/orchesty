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
    public function __construct(DocumentManager $dm)
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

        if (isset($filters['severity']) && $filters['severity'] !== '') {
            $filter['severity'] = $filters['severity'];
        }

        if ((isset($filters['from']) && $filters['from'] !== '') || (isset($filters['to']) && $filters['to'] !== '')) {
            $filter['createdAt'] = [];
            if (isset($filters['from']) && $filters['from'] !== '') {
                $filter['createdAt']['$gte'] = new UTCDateTime(new DateTime($filters['from']));
            }
            if (isset($filters['to']) && $filters['to'] !== '') {
                $filter['createdAt']['$lte'] = new UTCDateTime(new DateTime($filters['to']));
            }
        }

        $skip  = ($page - 1) * $limit;
        $total = $this->collection->countDocuments($filter);

        $cursor = $this->collection->find(
            $filter,
            [
                'limit' => $limit,
                'skip'  => $skip,
                'sort'  => ['createdAt' => -1],
            ],
        );

        $data = [];
        foreach ($cursor as $doc) {
            $data[] = $this->normalizeDocument((array) $doc);
        }

        return [
            'data'  => $data,
            'limit' => $limit,
            'page'  => $page,
            'total' => $total,
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
            'created_at'    => isset($doc['createdAt']) ? $doc['createdAt']->toDateTime()->format('c') : NULL,
            'event_type'    => $doc['eventType'] ?? '',
            'id'            => (string) ($doc['_id'] ?? ''),
            'message'       => $doc['message'] ?? '',
            'node_name'     => $doc['nodeName'] ?? NULL,
            'severity'      => $doc['severity'] ?? '',
            'tenant_id'     => $doc['tenantId'] ?? '',
            'topology_id'   => $doc['topologyId'] ?? NULL,
            'topology_name' => $doc['topologyName'] ?? NULL,
        ];
    }

}
