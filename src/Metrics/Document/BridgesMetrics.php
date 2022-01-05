<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class BridgesMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\Document(collection="pipes_node")
 * @ODM\Index(name="SearchIndex", keys={"tags.node_id"="text","tags.topology_id"="text"}),
 * @ODM\Index(name="createdIndex", keys={"created"="desc"})
 * @ODM\Index(name="expireIndex", keys={"timestamp"=1}, options={"expireAfterSeconds"=2628000})
 */
class BridgesMetrics
{

    use IdTrait;

    /**
     * @var BridgesMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\BridgesMetricsFields")
     */
    private BridgesMetricsFields $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\Tags")
     */
    private Tags $tags;

    /**
     * @return BridgesMetricsFields
     */
    public function getFields(): BridgesMetricsFields
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
