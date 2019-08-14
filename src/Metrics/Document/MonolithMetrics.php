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
 */
class MonolithMetrics
{

    use IdTrait;

    /**
     * @var MonolithMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="MonolithMetricsFields")
     */
    private $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Tags")
     */
    private $tags;

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
