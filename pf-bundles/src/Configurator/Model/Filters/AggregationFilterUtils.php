<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use MongoDB\BSON\UTCDateTime;
use OutOfRangeException;

/**
 * Class AggregationFilterUtils
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final readonly class AggregationFilterUtils
{

    /**
     * @param Builder $builder
     *
     * @return UTCDateTime
     */
    public static function getMiddleTimeFromAggregationBuilder(Builder $builder): UTCDateTime
    {
        try {
            [$gte, $lte] = self::getDates($builder);

            if ($gte === NULL || $lte === NULL) {
                return new UTCDateTime(0);
            }

            $lteTimestamp = $lte->toDateTime()->getTimestamp();
            $gteTimestamp = $gte->toDateTime()->getTimestamp();

            return new UTCDateTime(((int) ($gteTimestamp + ($lteTimestamp - $gteTimestamp) / 2)) * 1_000);
        } catch (OutOfRangeException) {
            return new UTCDateTime(0);
        }
    }

    /**
     * @param Builder $builder
     *
     * @return int
     */
    public static function getDateTruncBinSizeFromAggregationBuilder(Builder $builder): int
    {
        try {
            [$gte, $lte] = self::getDates($builder);

            if ($gte === NULL || $lte === NULL) {
                return 24;
            }

            $difference = $lte->toDateTime()->getTimestamp() - $gte->toDateTime()->getTimestamp();

            if ($difference <= 60 * 60) {
                return 5;
            }

            if ($difference <= 24 * 60 * 60) {
                return 2 * 60;
            }

            if ($difference <= 7 * 24 * 60 * 60) {
                return 12 * 60;
            }

            return 24 * 60;
        } catch (OutOfRangeException) {
            return 24 * 60;
        }
    }

    /**
     * @param Builder $builder
     * @return array{UTCDateTime|NULL, UTCDateTime|NULL}
     */
    private static function getDates(Builder $builder): array
    {
        $pipeline = $builder->getPipeline();

        /** @var mixed[]|null $and */
        $and = $pipeline[0]['$match']['$and'][0]['$and'][0]['$or'][0]['$and'] ?? NULL;
        /** @var UTCDateTime|NULL $gte */
        $gte = $and[0]['fields.created']['$gte'] ?? NULL;
        /** @var UTCDateTime|NULL $lte */
        $lte = $and[1]['fields.created']['$lte'] ?? NULL;

        return [$gte, $lte];
    }

}
