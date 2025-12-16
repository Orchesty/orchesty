<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class UserTaskMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'userTask')]
#[ODM\Index(
    keys: ['fields.created' => 'asc'],
    name: 'IK_userTask_fieldsCreated',
    expireAfterSeconds: 2_628_000,
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_userTask_fieldsCreated_fieldsMessages',
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'tags.nodeId' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_userTask_fieldsCreated_tagsNodeId_fieldsMessages',
)]
#[ODM\Index(
    keys: ['fields.created' => 'asc', 'tags.nodeId' => 'asc', 'tags.topologyId' => 'asc', 'fields.messages' => 'asc'],
    name: 'IK_userTask_fieldsCreated_tagsNodeId_tagsTopologyId_fieldsMessages',
)]
class UserTaskMetrics
{

    use IdTrait;

    /**
     * @var UserTaskMetricsFields
     */
    #[ODM\EmbedOne(targetDocument: UserTaskMetricsFields::class)]
    private UserTaskMetricsFields $fields;

    /**
     * @var TagsCamelCase
     */
    #[ODM\EmbedOne(targetDocument: TagsCamelCase::class)]
    private TagsCamelCase $tags;

    /**
     * UserTaskMetrics constructor.
     *
     * @param UserTaskMetricsFields $fields
     * @param TagsCamelCase         $tags
     */
    public function __construct(UserTaskMetricsFields $fields, TagsCamelCase $tags)
    {
        $this->fields = $fields;
        $this->tags   = $tags;
    }

    /**
     * @return UserTaskMetricsFields
     */
    public function getFields(): UserTaskMetricsFields
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
