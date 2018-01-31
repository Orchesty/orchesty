<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\SystemMetrics;

use CleverConnectors\AppBundle\Utils\DateTimeUtils;
use Elastica\Client;
use Elastica\Request;

/**
 * Class SystemMetrics
 *
 * @package CleverConnectors\AppBundle\Model\SystemMetrics
 */
class SystemMetrics implements SystemMetricsInterface
{

    private const PATH = 'index/limiter/_search';

    private const SYSTEM_METRICS_GROUP_BY = 'group_by_timestamp';
    private const SYSTEM_REQUEST_GROUP_BY = 'group_by_system-key';

    /**
     * @var Client
     */
    private $client;

    /**
     * SystemMetrics constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param SystemMetricsDto $dto
     *
     * @return array
     */
    public function getSystemMetrics(SystemMetricsDto $dto): array
    {
        $query = [
            'aggs' => [
                self::SYSTEM_METRICS_GROUP_BY => [
                    'range' => [
                        'field'  => 'timestamp',
                        'ranges' => $this->generateRanges($dto),
                    ],
                ],
            ],
        ];

        $query = json_encode($this->processParameters($dto, $query));

        return $this->processSystemMetrics($this->client->request(self::PATH, Request::GET, $query)->getData());
    }

    /**
     * @param SystemMetricsDto $dto
     *
     * @return int
     */
    public function getSystemRequestCount(SystemMetricsDto $dto): int
    {
        $query = [
            'aggs' => [
                self::SYSTEM_REQUEST_GROUP_BY => [
                    'terms' => [
                        'field' => 'system-key.keyword',
                    ],
                ],
            ],
        ];

        $query = json_encode($this->processParameters($dto, $query));

        return $this->processSystemRequestCount($this->client->request(self::PATH, Request::GET, $query)->getData());
    }

    /**
     * @param SystemMetricsDto $dto
     * @param array            $query
     *
     * @return array
     */
    private function processParameters(SystemMetricsDto $dto, array $query): array
    {
        $counter = 0;

        $query['query']['bool']['must'][$counter++]['term']['system-key.keyword'] = $dto->getSystemKey();

        if ($dto->getFrom()) {
            $query['query']['bool']['must'][$counter]['range']['timestamp']['gte'] = (int) $dto->getFrom()->format('U');
        }

        if ($dto->getTo()) {
            $query['query']['bool']['must'][$counter]['range']['timestamp']['lte'] = (int) $dto->getTo()->format('U');
        }

        if ($dto->getGuid()) {
            $query['query']['bool']['must'][++$counter]['term']['guid'] = $dto->getGuid();
        }

        return $query;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function processSystemMetrics(array $data): array
    {
        $result = [];

        if (isset($data['aggregations'][self::SYSTEM_METRICS_GROUP_BY]['buckets'])) {
            foreach ($data['aggregations']['group_by_timestamp']['buckets'] as $item) {
                $result[$item['from']] = $item['doc_count'];
            }
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return int
     */
    private function processSystemRequestCount(array $data): int
    {
        $result = [];

        if (isset($data['aggregations'][self::SYSTEM_REQUEST_GROUP_BY]['buckets'])) {
            foreach ($data['aggregations']['group_by_system-key']['buckets'] as $item) {
                $result[] = $item['doc_count'];
            }
        }

        return $result ? array_values($result)[0] : 0;
    }

    /**
     * @param SystemMetricsDto $dto
     *
     * @return array
     */
    private function generateRanges(SystemMetricsDto $dto): array
    {
        $ranges = [];

        $from = $dto->getFrom() ? clone $dto->getFrom() : DateTimeUtils::getUTCDateTimeFromTimeStamp();
        $to   = $dto->getTo() ? clone $dto->getTo() : DateTimeUtils::getUTCDateTime();

        while ($from < $to) {
            $ranges[] = [
                'from' => (int) $from->format('U'),
                'to'   => (int) $from->modify(sprintf('+1 %s', $dto->getInterval()))->format('U'),
            ];
        }

        return $ranges;
    }

}