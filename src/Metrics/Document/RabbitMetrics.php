<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class RabbitMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\Document(collection="rabbitmq")
 * @ODM\Index(name="queueIndex", keys={"tags.queue"="text"})
 * @ODM\Index(name="createdIndex", keys={"fields.created"="desc"})
 * @ODM\Index(name="expireIndex", keys={"fields.created"=1}, options={"expireAfterSeconds"=2628000})
 */
class RabbitMetrics
{

    use IdTrait;

    /**
     * @var RabbitMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\RabbitMetricsFields")
     */
    private RabbitMetricsFields $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\Tags")
     */
    private Tags $tags;

    /**
     * @return RabbitMetricsFields
     */
    public function getFields(): RabbitMetricsFields
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
