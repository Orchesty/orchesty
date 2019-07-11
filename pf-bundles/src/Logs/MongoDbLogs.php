<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Document\Node;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoException;

/**
 * Class MongoDbLogs
 *
 * @package Hanaboso\PipesFramework\Logs
 */
class MongoDbLogs implements LogsInterface
{

    private const CORRELATION_ID = 'correlation_id';
    private const TOPOLOGY_ID    = 'topology_id';
    private const TOPOLOGY_NAME  = 'topology_name';
    private const NODE_ID        = 'node_id';
    private const NODE_NAME      = 'node_name';
    private const PIPES          = 'pipes';

    private const SEVERITY = 'severity';
    private const MESSAGE  = 'message';
    private const TYPE     = 'type';

    private const LIMIT = 1000;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var LogsFilter
     */
    private $filter;

    /**
     * @var StartingPointsFilter
     */
    private $startingPointsFilter;

    /**
     * MongoDbLogs constructor.
     *
     * @param DocumentManager      $dm
     * @param LogsFilter           $filter
     * @param StartingPointsFilter $startingPointsFilter
     */
    public function __construct(DocumentManager $dm, LogsFilter $filter, StartingPointsFilter $startingPointsFilter)
    {
        $this->dm                   = $dm;
        $this->filter               = $filter;
        $this->startingPointsFilter = $startingPointsFilter;
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return array
     * @throws MongoDBException
     * @throws GridException
     * @throws MongoException
     */
    public function getData(GridRequestDto $dto): array
    {
        $data           = $this->filter->getData($dto)->toArray();
        $result         = [];
        $correlationIds = [];

        foreach ($data as $item) {
            $pipes = $item[self::PIPES] ?? [];

            $result[] = [
                'id'                 => array_key_exists('id', $item) ? (string) $item['id'] : '',
                self::SEVERITY       => $pipes[self::SEVERITY] ?? '',
                self::MESSAGE        => $item[self::MESSAGE] ?? '',
                self::TYPE           => $pipes[self::TYPE] ?? '',
                self::CORRELATION_ID => $pipes[self::CORRELATION_ID] ?? '',
                self::TOPOLOGY_ID    => $pipes[self::TOPOLOGY_ID] ?? '',
                self::TOPOLOGY_NAME  => $pipes[self::TOPOLOGY_NAME] ?? '',
                self::NODE_ID        => $pipes[self::NODE_ID] ?? '',
                self::NODE_NAME      => $pipes[self::NODE_NAME] ?? '',
                'timestamp'          => str_replace('"', '', $item['@timestamp'] ?? ''),
            ];

            $correlationId = $this->getNonEmptyValue($pipes, self::CORRELATION_ID);

            if ($correlationId) {
                $correlationIds[] = $correlationId;
            }
        }

        $innerDto = new GridRequestDto(['limit' => self::LIMIT]);
        $innerDto->setAdditionalFilters([self::CORRELATION_ID => $correlationIds]);

        $result = $this->processStartingPoints($innerDto, $result);
        $count  = $dto->getParamsForHeader()['total'];

        return [
            'limit'  => $dto->getLimit(),
            'offset' => ((int) ($dto->getPage() ?? 1) - 1) * $dto->getLimit(),
            'count'  => count($result),
            'total'  => $count >= self::LIMIT ? self::LIMIT : $count,
            'items'  => $result,
        ];
    }

    /**
     * @param GridRequestDto $dto
     * @param array          $result
     *
     * @return array
     * @throws GridException
     * @throws MongoDBException
     * @throws MongoException
     */
    private function processStartingPoints(GridRequestDto $dto, array $result): array
    {
        $data        = $this->startingPointsFilter->getData($dto)->toArray();
        $innerResult = [];

        foreach ($data as $item) {
            $innerResult[$item[self::PIPES][self::CORRELATION_ID]] = $item;
        }

        foreach ($result as $key => $item) {
            $correlationId = $this->getNonEmptyValue($item, self::CORRELATION_ID);
            $nodeId        = $this->getNonEmptyValue($item, self::NODE_ID);

            if (is_array($correlationId)) {
                throw new LockException('Bad data format.');
            }

            if (is_array($nodeId)) {
                throw new LockException('Bad data format.');
            }

            if ($correlationId && $this->getNonEmptyValue($innerResult, $correlationId)) {
                $result[$key][self::TOPOLOGY_ID]   = $innerResult[$correlationId][self::PIPES][self::TOPOLOGY_ID];
                $result[$key][self::TOPOLOGY_NAME] = $innerResult[$correlationId][self::PIPES][self::TOPOLOGY_NAME];
            }

            if ($nodeId) {
                $result[$key][self::NODE_NAME] = $this->getNodeName($nodeId);
            }
        }

        return $result;
    }

    /**
     * @param string $nodeId
     *
     * @return string
     */
    private function getNodeName(string $nodeId): string
    {
        try {
            /** @var Node|NULL $node */
            $node = $this->dm->getRepository(Node::class)->findOneBy(['_id' => new ObjectId(explode('-', $nodeId)[0])]);

            return $node ? $node->getName() : '';
        } catch (InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * @param array  $data
     * @param string $property
     *
     * @return array|string|NULL
     */
    private function getNonEmptyValue(array $data, string $property)
    {
        return array_key_exists($property, $data) && $data[$property] !== '' ? $data[$property] : NULL;
    }

}
