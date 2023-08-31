<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Stacktrace
 *
 * @package Hanaboso\PipesFramework\Logs\Document
 *
 * @ODM\EmbeddedDocument()
 */
class Stacktrace
{

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $message;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $class;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $file;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $trace;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $code;

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getTrace(): string
    {
        return $this->trace;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

}
