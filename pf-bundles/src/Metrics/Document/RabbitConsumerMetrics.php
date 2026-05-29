<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class RabbitConsumerMetrics
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\Document(collection: 'rabbitmq_consumer')]
#[ODM\Index(keys: ['tags.queue' => 'text'], name: 'queueIndex')]
#[ODM\Index(keys: ['fields.created' => 'desc'], name: 'createdIndex')]
#[ODM\Index(keys: ['fields.created' => 'asc'], name: 'expireIndex', expireAfterSeconds: 2_628_000)]
class RabbitConsumerMetrics
{

    use IdTrait;

    /**
     * @var RabbitConsumerFields
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\RabbitConsumerFields')]
    private RabbitConsumerFields $fields;

    /**
     * @var Tags
     */
    #[ODM\EmbedOne(targetDocument: 'Hanaboso\PipesFramework\Metrics\Document\Tags')]
    private Tags $tags;

    /**
     * @return RabbitConsumerFields
     */
    public function getFields(): RabbitConsumerFields
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
