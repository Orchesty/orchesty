<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class LimiterMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'limiter')]
#[ODM\Index(
    keys: ['fields.created' => 'asc'],
    name: 'IK_limiter_fieldsCreated',
    expireAfterSeconds: 2_628_000,
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_limiter_fieldsCreated_fieldsMessages',
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'tags.nodeId' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_limiter_fieldsCreated_tagsNodeId_fieldsMessages',
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'tags.nodeId' => 'asc', 'tags.topologyId' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_limiter_fieldsCreated_tagsNodeId_tagsTopologyId_fieldsMessages',
)]
class LimiterMetrics
{

    use IdTrait;

    /**
     * @var LimiterMetricsFields
     */
    #[ODM\EmbedOne(targetDocument: LimiterMetricsFields::class)]
    private LimiterMetricsFields $fields;

    /**
     * @var TagsCamelCase
     */
    #[ODM\EmbedOne(targetDocument: TagsCamelCase::class)]
    private TagsCamelCase $tags;

    /**
     * LimiterMetrics constructor.
     *
     * @param LimiterMetricsFields $fields
     * @param TagsCamelCase        $tags
     */
    public function __construct(LimiterMetricsFields $fields, TagsCamelCase $tags)
    {
        $this->fields = $fields;
        $this->tags   = $tags;
    }

    /**
     * @return LimiterMetricsFields
     */
    public function getFields(): LimiterMetricsFields
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
