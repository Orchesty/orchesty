<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class ConnectorsMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\Document(collection="connectors")
 * @ODM\Index(name="SearchIndex", keys={"tags.node_id"="text","tags.topology_id"="text","tags.application_id"="text","tags.user_id"="text","tags.correlation_id"="text"}),
 * @ODM\Index(name="createdIndex", keys={"created"="desc"})
 * @ODM\Index(name="expireIndex", keys={"timestamp"=1}, options={"expireAfterSeconds"=2628000})
 */
class ConnectorsMetrics
{

    use IdTrait;

    /**
     * @var ConnectorsMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetricsFields")
     */
    private ConnectorsMetricsFields $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\Tags")
     */
    private Tags $tags;

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
