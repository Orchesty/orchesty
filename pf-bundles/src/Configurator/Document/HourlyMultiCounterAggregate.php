<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class HourlyMultiCounterAggregate
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 */
#[ODM\Document(collection: 'hourly_multi_counter_aggregates')]
#[ODM\Index(
    keys: ['bucket' => 'asc'],
    name: 'IK_hourlyMultiCounterAggregate_bucket',
    expireAfterSeconds: 2_628_000,
)]
#[ODM\Index(
    keys: ['topology_id' => 'asc', 'bucket' => 'asc'],
    name: 'UK_hourlyMultiCounterAggregate_topologyId_bucket',
    unique: TRUE,
)]
class HourlyMultiCounterAggregate
{

    use IdTrait;

}
