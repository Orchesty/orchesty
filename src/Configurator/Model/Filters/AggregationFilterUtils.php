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

    public const string BUCKETS         = 'buckets';
    public const int    DEFAULT_BUCKETS = 10;

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
     * @param int     $buckets
     *
     * @return array{int, UTCDateTime|NULL, UTCDateTime|NULL, UTCDateTime|NULL}
     */
    public static function getDensifyBinSizeAndRangeFromAggregationBuilder(Builder $builder, int $buckets): array
    {
        try {
            [$gte, $lte] = self::getDates($builder);

            if ($gte === NULL || $lte === NULL) {
                return [24 * 60 * 60 * 1_000, NULL, NULL, NULL];
            }

            $rangeSeconds = $lte->toDateTime()->getTimestamp() - $gte->toDateTime()->getTimestamp();
            $binSizeMs    = max(1_000, (int) ceil($rangeSeconds / max(1, $buckets)) * 1_000);
            $gteMs        = (int) (string) $gte;
            $densifyStart = new UTCDateTime($gteMs + $binSizeMs);
            $densifyEnd   = new UTCDateTime($gteMs + $buckets * $binSizeMs + 1);

            return [$binSizeMs, $gte, $densifyStart, $densifyEnd];
        } catch (OutOfRangeException) {
            return [24 * 60 * 60 * 1_000, NULL, NULL, NULL];
        }
    }

    /**
     * @param Builder $builder
     * @return array{UTCDateTime|NULL, UTCDateTime|NULL}
     */
    public static function getDates(Builder $builder): array
    {
        $pipeline = $builder->getPipeline();

        /** @var mixed[]|null $or */
        $or = $pipeline[0]['$match']['$and'][0]['$or'] ?? NULL;
        /** @var UTCDateTime|NULL $gte */
        $gte = $or[0]['fields.created']['$gte'] ?? $or[0]['created']['$gte'] ?? NULL;
        /** @var UTCDateTime|NULL $lte */
        $lte = $or[0]['fields.created']['$lte'] ?? $or[0]['created']['$lte'] ?? NULL;

        return [$gte, $lte];
    }

}
