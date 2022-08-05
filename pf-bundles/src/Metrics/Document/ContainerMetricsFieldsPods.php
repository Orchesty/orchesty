<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class ContainerMetricsFieldsPods
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 */
class ContainerMetricsFieldsPods
{

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
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $age;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private int $restarts;

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
     * @return string
     */
    public function getAge(): string
    {
        return $this->age;
    }

    /**
     * @return int
     */
    public function getRestarts(): int
    {
        return $this->restarts;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'restarts' => $this->restarts,
            'up'       => $this->up,
            'message'  => $this->message,
            'age'      => $this->age,
        ];
    }

}
