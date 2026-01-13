<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class ConnectorsMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'connectors')]
#[ODM\Index(
    keys: ['fields.created' => 'asc'],
    name: 'IK_connector_fieldsCreated',
    expireAfterSeconds: 2_628_000,
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'tags.node_id' => 'asc'],
    name: 'IK_connector_fieldsCreated_tagsNodeId',
)]
#[ODM\Index(
    keys: ['tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_tagsNodeId_fieldsCreated',
)]
#[ODM\Index(
    keys: ['tags.topology_id' => 'asc', 'tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_tagsTopologyId_tagsNodeId_fieldsCreated',
)]
#[ODM\Index(
    keys: ['tags.application_id' => 'asc', 'tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_tagsApplicationId_tagsNodeId_fieldsCreated',
)]
#[ODM\Index(
    keys: ['fields.response_code' => 'asc', 'tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_fieldsResponseCode_tagsNodeId_fieldsCreated',
)]
#[ODM\Index(
    keys: ['fields.response_code' => 'asc', 'tags.topology_id' => 'asc', 'tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_fieldsResponseCode_tagsTopologyId_tagsNodeId_fieldsCreated',
)]
#[ODM\Index(
    keys: ['fields.response_code' => 'asc', 'tags.application_id' => 'asc', 'tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_fieldsResponseCode_tagsApplicationId_tagsNodeId_fieldsCreated',
)]
#[ODM\Index(
    keys: ['tags.application_id' => 'asc', 'tags.topology_id' => 'asc', 'tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_tagsApplicationId_tagsTopologyId_tagsNodeId_fieldsCreated',
)]
#[ODM\Index(
    keys: ['fields.response_code' => 'asc', 'tags.application_id' => 'asc', 'tags.topology_id' => 'asc', 'tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_fieldsResponseCode_tagsApplicationId_tagsTopologyId_tagsNodeId_fieldsCreated',
)]
#[ODM\Index(
    keys: ['tags.correlation_id' => 'asc', 'tags.node_id' => 'asc', 'fields.created' => 'asc'],
    name: 'IK_connector_tagsCorrelationId_tagsNodeId_fieldsCreated',
)]
class ConnectorsMetrics
{

    use IdTrait;

    /**
     * @var ConnectorsMetricsFields
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetricsFields')]
    private ConnectorsMetricsFields $fields;

    /**
     * @var Tags
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\Tags')]
    private Tags $tags;

    /**
     * ConnectorsMetrics constructor.
     *
     * @param ConnectorsMetricsFields $fields
     * @param Tags                    $tags
     */
    public function __construct(ConnectorsMetricsFields $fields, Tags $tags)
    {
        $this->fields = $fields;
        $this->tags   = $tags;
    }

    /**
     * @return ConnectorsMetricsFields
     */
    public function getFields(): ConnectorsMetricsFields
    {
        return $this->fields;
    }

    /**
     * @return Tags
     */
    public function getTags(): Tags
    {
        return $this->tags;
    }

}
