<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ProgressFilter;
use Hanaboso\Utils\Date\DateTimeUtils;

/**
 * Class ProgressManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class ProgressManager
{

    /**
     * ProgressManager constructor.
     *
     * @param ProgressFilter $progressFilter
     */
    public function __construct(private ProgressFilter $progressFilter)
    {
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws MongoDBException
     * @throws Exception
     */
    public function getProgress(GridRequestDtoInterface $dto): array
    {
        $res = $this->progressFilter->getData($dto)->toArray();

        return array_map(static function (array $doc) {
            $finished = $doc['finished'] ?
                DateTimeUtils::getUtcDateTime($doc['finished'])->format(DateTimeUtils::DATE_TIME_UTC) :
                NULL;
            $end      = $doc['finished'] ?? DateTimeUtils::getUtcDateTime()->format(DateTimeUtils::DATE_TIME_UTC);
            $count    = $doc['ok'] + $doc['nok'];
            $created  = DateTimeUtils::getUtcDateTime($doc['created']);

            return [
                'id'             => $doc['topologyId'],
                'correlationId'  => $doc['_id'] ?? $doc['id'],
                'duration'       => TopologyProgress::durationInMs($created, DateTimeUtils::getUtcDateTime($end)),
                'started'        => $created->format(DateTimeUtils::DATE_TIME_UTC),
                'finished'       => $finished,
                'nodesProcessed' => $count,
                'nodesTotal'     => $doc['total'],
                'status'         => $count < $doc['total'] ? 'IN PROGRESS' : ($doc['nok'] > 0 ? 'FAILED' : 'SUCCESS'),
                'failed'         => $doc['nok'],
                'user'           => $doc['user'],
            ];
        }, $res);
    }

}
