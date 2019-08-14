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
 */
class BridgesMetrics
{

    use IdTrait;

    /**
     * @var BridgesMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="BridgesMetricsFields")
     */
    private $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Tags")
     */
    private $tags;

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
