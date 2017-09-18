<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder;

/**
 * Class GenericAttachmentAbstract
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder
 */
abstract class GenericAttachmentAbstract
{

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var null|string
     */
    private $filename;

    /**
     * GenericContentAttachment constructor.
     *
     * @param string      $contentType
     * @param null|string $filename
     */
    public function __construct(string $contentType, ?string $filename = NULL)
    {
        $this->contentType = $contentType;
        $this->filename    = $filename;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return null|string
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

}