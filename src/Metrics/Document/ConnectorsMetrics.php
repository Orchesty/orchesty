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
 */
class ConnectorsMetrics
{

    use IdTrait;

    /**
     * @var ConnectorsMetricsFields
     *
     * @ODM\EmbedOne(targetDocument="ConnectorsMetricsFields")
     */
    private $fields;

    /**
     * @var Tags
     *
     * @ODM\EmbedOne(targetDocument="Tags")
     */
    private $tags;

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
