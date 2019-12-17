<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\GridRequestDto;

/**
 * Class MongoDbLogs
 *
 * @package Hanaboso\PipesFramework\Logs
 */
final class MongoDbLogs extends LogsAbstract
{

    /**
     * @var LogsFilter
     */
    private $filter;

    /**
     * MongoDbLogs constructor.
     *
     * @param DocumentManager      $dm
     * @param LogsFilter           $filter
     * @param StartingPointsFilter $startingPointsFilter
     */
    public function __construct(DocumentManager $dm, LogsFilter $filter, StartingPointsFilter $startingPointsFilter)
    {
        parent::__construct($dm, $startingPointsFilter);

        $this->filter = $filter;
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws Exception
     */
    public function getData(GridRequestDto $dto): array
    {
        $data           = $this->filter->getData($dto)->toArray();
        $result         = [];
        $correlationIds = [];

        foreach ($data as $item) {
            $pipes = $item[self::PIPES] ?? [];

            $result[] = [
                self::ID             => array_key_exists(self::ID, $item) ? (string) $item[self::ID] : '',
                self::SEVERITY       => $pipes[self::SEVERITY] ?? '',
                self::MESSAGE        => $item[self::MESSAGE] ?? '',
                self::TYPE           => $pipes[self::TYPE] ?? '',
                self::CORRELATION_ID => $pipes[self::CORRELATION_ID] ?? '',
                self::TOPOLOGY_ID    => $pipes[self::TOPOLOGY_ID] ?? '',
                self::TOPOLOGY_NAME  => $pipes[self::TOPOLOGY_NAME] ?? '',
                self::NODE_ID        => $pipes[self::NODE_ID] ?? '',
                self::NODE_NAME      => $pipes[self::NODE_NAME] ?? '',
                self::TIMESTAMP      => str_replace('"', '', $item[self::TIMESTAMP_PREFIX] ?? ''),
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

}
