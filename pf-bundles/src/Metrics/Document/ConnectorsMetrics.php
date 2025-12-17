<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class ConnectorsMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'connectors')]
#[ODM\Index(
    keys: [
        'tags.application_id' => 'text',
        'tags.correlation_id' => 'text',
        'tags.node_id' => 'text',
        'tags.topology_id' => 'text',
        'tags.user_id' => 'text',
    ],
    name: 'SearchIndex',
)]
#[ODM\Index(keys: ['fields.created' => 'desc'], name: 'createdIndex')]
#[ODM\Index(keys: ['timestamp' => 'asc'], name: 'expireIndex', expireAfterSeconds: 2_628_000)]
class ConnectorsMetrics
{

    use IdTrait;

    /**
     * @var ConnectorsMetricsFields
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetricsFields')]
    private ConnectorsMetricsFields $fields;

    /**
     * @var Tags
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\Tags')]
    private Tags $tags;

    /**
     * ConnectorsMetrics constructor.
     *
     * @param ConnectorsMetricsFields $fields
     * @param Tags                    $tags
     */
    public function __construct(ConnectorsMetricsFields $fields, Tags $tags)
    {
        $this->fields = $fields;
        $this->tags   = $tags;
    }

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
