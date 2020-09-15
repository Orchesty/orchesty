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
