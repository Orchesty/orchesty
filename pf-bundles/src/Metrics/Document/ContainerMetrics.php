<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class ContainerMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\Document(collection="container")
 * @ODM\Index(name="createdIndex", keys={"fields.created"="desc"})
 */
class ContainerMetrics
{

    use IdTrait;

    /**
     * @var ContainerMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\ContainerMetricsFields")
     */
    private ContainerMetricsFields $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Hanaboso\PipesFramework\Metrics\Document\Tags")
     */
    private Tags $tags;

    /**
     * @return ContainerMetricsFields
     */
    public function getFields(): ContainerMetricsFields
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
