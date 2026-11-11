<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class HourlyPipesNodeAggregate
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'hourly_pipes_node_aggregates')]
#[ODM\Index(
    keys: ['node_id' => 'asc', 'bucket' => 'asc'],
    name: 'UK_hourlyPipesNodeAggregate_nodeId_bucket',
    unique: TRUE,
)]
class HourlyPipesNodeAggregate
{

    use IdTrait;

}
