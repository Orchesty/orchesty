<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class MonolithMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'monolith')]
#[ODM\Index(keys: ['tags.node_id' => 'text', 'tags.topology_id' => 'text'], name: 'SearchIndex')]
#[ODM\Index(keys: ['fields.created' => 'desc'], name: 'createdIndex')]
#[ODM\Index(keys: ['fields.created' => 'asc'], name: 'expireIndex', expireAfterSeconds: 2_628_000)]
class MonolithMetrics
{

    use IdTrait;

    /**
     * @var MonolithMetricsFields
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\MonolithMetricsFields')]
    private MonolithMetricsFields $fields;

    /**
     * @var Tags
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\Tags')]
    private Tags $tags;

    /**
     * @return MonolithMetricsFields
     */
    public function getFields(): MonolithMetricsFields
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
