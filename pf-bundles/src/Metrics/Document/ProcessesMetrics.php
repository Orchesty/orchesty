<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class ProcessesMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'pipes_counter')]
#[ODM\Index(keys: ['tags.node_id' => 'text'], name: 'node_idIndex')]
#[ODM\Index(keys: ['fields.created' => 'desc'], name: 'createdIndex')]
#[ODM\Index(keys: ['fields.created' => 'asc'], name: 'expireIndex', expireAfterSeconds: 2_628_000)]
class ProcessesMetrics
{

    use IdTrait;

    /**
     * @var ProcessesMetricsFields
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\ProcessesMetricsFields')]
    private ProcessesMetricsFields $fields;

    /**
     * @var Tags
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\Tags')]
    private Tags $tags;

    /**
     * @return ProcessesMetricsFields
     */
    public function getFields(): ProcessesMetricsFields
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
