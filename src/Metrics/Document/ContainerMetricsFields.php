<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class ContainerMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 */
class ContainerMetricsFields
{

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $name;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $message;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    private bool $up;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private DateTime $created;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isUp(): bool
    {
        return $this->up;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

}
