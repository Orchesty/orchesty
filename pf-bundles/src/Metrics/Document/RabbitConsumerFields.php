<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class RabbitConsumerFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 */
class RabbitConsumerFields
{

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="consumers")
     */
    private int $consumers;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private DateTime $created;

    /**
     * @return int
     */
    public function getConsumers(): int
    {
        return $this->consumers;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

}
