<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class RepeaterMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'repeater')]
#[ODM\Index(
    keys: ['fields.created' => 'asc'],
    name: 'IK_repeater_fieldsCreated',
    expireAfterSeconds: 2_628_000,
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_repeater_fieldsCreated_fieldsMessages',
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'tags.nodeId' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_repeater_fieldsCreated_tagsNodeId_fieldsMessages',
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'tags.nodeId' => 'asc', 'tags.topologyId' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_repeater_fieldsCreated_tagsNodeId_tagsTopologyId_fieldsMessages',
)]
class RepeaterMetrics
{

    use IdTrait;

    /**
     * @var RepeaterMetricsFields
     */
    #[ODM\EmbedOne(targetDocument: RepeaterMetricsFields::class)]
    private RepeaterMetricsFields $fields;

    /**
     * @var TagsCamelCase
     */
    #[ODM\EmbedOne(targetDocument: TagsCamelCase::class)]
    private TagsCamelCase $tags;

    /**
     * RepeaterMetrics constructor.
     *
     * @param RepeaterMetricsFields $fields
     * @param TagsCamelCase         $tags
     */
    public function __construct(RepeaterMetricsFields $fields, TagsCamelCase $tags)
    {
        $this->fields = $fields;
        $this->tags   = $tags;
    }

    /**
     * @return RepeaterMetricsFields
     */
    public function getFields(): RepeaterMetricsFields
    {
        return $this->fields;
    }

    /**
     * @return TagsCamelCase
     */
    public function getTags(): TagsCamelCase
    {
        return $this->tags;
    }

}
