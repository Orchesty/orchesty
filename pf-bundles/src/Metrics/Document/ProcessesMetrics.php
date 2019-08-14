<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class ProcessesMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\Document(collection="pipes_counter")
 */
class ProcessesMetrics
{

    use IdTrait;

    /**
     * @var ProcessesMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="ProcessesMetricsFields")
     */
    private $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Tags")
     */
    private $tags;

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
