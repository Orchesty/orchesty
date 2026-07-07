<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class HourlyConnectorsAggregate
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'hourly_connectors_aggregates')]
#[ODM\Index(
    keys: ['node_id' => 'asc', 'bucket' => 'asc'],
    name: 'UK_hourlyConnectorAggregate_nodeId_bucket',
    unique: TRUE,
)]
class HourlyConnectorsAggregate
{

    use IdTrait;

}
