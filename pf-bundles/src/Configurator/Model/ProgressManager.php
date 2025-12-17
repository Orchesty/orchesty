<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ProgressFilter;
use Hanaboso\PipesFramework\Database\Document\Topology;
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
     * @param ProgressFilter  $progressFilter
     * @param DocumentManager $dm
     */
    public function __construct(private readonly ProgressFilter $progressFilter, private readonly DocumentManager $dm)
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

        return array_map(function (array $doc) {
            $finished = $doc['finished']
                ? DateTimeUtils::getUtcDateTime($doc['finished'])->format(DateTimeUtils::DATE_TIME_UTC)
                : NULL;
            $end      = $doc['finished'] ?? DateTimeUtils::getUtcDateTime()->format(DateTimeUtils::DATE_TIME_UTC);
            $created  = DateTimeUtils::getUtcDateTime($doc['created']);

            $topo = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $doc['topologyId']]);

            return [
                'correlationId'  => $doc['_id'] ?? $doc['id'],
                'duration'       => TopologyProgress::durationInMs($created, DateTimeUtils::getUtcDateTime($end)),
                'failed'         => $doc['nok'],
                'finished'       => $finished,
                'id'             => $doc['topologyId'],
                'name'           => $topo?->getName() ?? '',
                'nodesProcessed' => $doc['processedCount'],
                'nodesTotal'     => $doc['total'],
                'process'        => $topo?->getDescr() ?? '',
                'started'        => $created->format(DateTimeUtils::DATE_TIME_UTC),
                'status'         => $doc['processedCount'] < $doc['total'] ? 'IN PROGRESS' : ($doc['nok'] > 0 ? 'FAILED' : 'SUCCESS'),
                'user'           => $doc['user'] ?? '',
            ];
        }, $res);
    }

}
