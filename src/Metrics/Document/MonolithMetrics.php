<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class MonolithMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\Document(collection="monolith")
 * @ODM\Index(name="SearchIndex", keys={"tags.node_id"="text","tags.topology_id"="text"}),
 * @ODM\Index(name="createdIndex", keys={"created"="desc"})
 * @ODM\Index(name="expireIndex", keys={"timestamp"=1}, options={"expireAfterSeconds"=2628000})
 */
class MonolithMetrics
{

    use IdTrait;

    /**
     * @var MonolithMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\MonolithMetricsFields")
     */
    private MonolithMetricsFields $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\Tags")
     */
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
