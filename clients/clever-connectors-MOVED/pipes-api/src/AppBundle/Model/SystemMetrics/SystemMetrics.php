<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\SystemMetrics;

use CleverConnectors\AppBundle\Utils\DateTimeUtils;
use Elastica\Client;
use Elastica\Request;
use Exception;

/**
 * Class SystemMetrics
 *
 * @package CleverConnectors\AppBundle\Model\SystemMetrics
 */
class SystemMetrics implements SystemMetricsInterface
{

    private const PATH = '%s/limiter/_search';

    private const GROUP_BY_TIMESTAMP  = 'group_by_timestamp';
    private const GROUP_BY_SYSTEM_KEY = 'group_by_system-key';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $index;

    /**
     * SystemMetrics constructor.
     *
     * @param Client $client
     * @param string $index
     */
    public function __construct(Client $client, string $index)
    {
        $this->client = $client;
        $this->index  = $index;
    }

    /**
     * @param SystemMetricsDto $dto
     *
     * @return array
     * @throws Exception
     */
    public function getSystemMetrics(SystemMetricsDto $dto): array
    {
        $query = [
            'aggs' => [
                self::GROUP_BY_SYSTEM_KEY => [
                    'terms' => [
                        'field' => 'system-key.keyword',
                    ],
                    'aggs'  => [
                        self::GROUP_BY_TIMESTAMP => [
                            'range' => [
                                'field'  => 'timestamp',
                                'ranges' => $this->generateRanges($dto),
                            ],
                        ],
                    ],
                ],

            ],
        ];

        $query = json_encode($this->processParameters($dto, $query));
        $res   = $this->processSystemMetrics($this->client->request($this->getPath(), Request::GET, $query)->getData());

        return $res;
    }

    /**
     * @param SystemMetricsDto $dto
     *
     * @return array
     * @throws Exception
     */
    public function getSystemRequestCount(SystemMetricsDto $dto): array
    {
        $query = [
            'aggs' => [
                self::GROUP_BY_SYSTEM_KEY => [
                    'terms' => [
                        'field' => 'system-key.keyword',
                    ],
                ],
            ],
        ];

        $query = json_encode($this->processParameters($dto, $query));

        $res = $this->processSystemRequestCount(
            $this->client->request($this->getPath(), Request::GET, $query)->getData()
        );

        foreach ($dto->getSystemKeys() as $systemKey) {
            $res[$systemKey] = $res[$systemKey] ?? 0;
        }

        return $res;
    }

    /**
     * @return string
     */
    private function getPath(): string
    {
        return sprintf(self::PATH, $this->index);
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

        $query['query']['bool']['must'][$counter++]['terms']['system-key.keyword'] = $dto->getSystemKeys();

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

        if (isset($data['aggregations'][self::GROUP_BY_SYSTEM_KEY]['buckets'])) {
            foreach ($data['aggregations'][self::GROUP_BY_SYSTEM_KEY]['buckets'] as $system) {
                if (isset($system[self::GROUP_BY_TIMESTAMP]['buckets'])) {
                    foreach ($system[self::GROUP_BY_TIMESTAMP]['buckets'] as $item) {
                        $result[$system['key']][$item['from']] = $item['doc_count'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function processSystemRequestCount(array $data): array
    {
        $result = [];

        if (isset($data['aggregations'][self::GROUP_BY_SYSTEM_KEY]['buckets'])) {
            foreach ($data['aggregations'][self::GROUP_BY_SYSTEM_KEY]['buckets'] as $item) {
                $result[$item['key']] = $item['doc_count'];
            }
        }

        return $result;
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