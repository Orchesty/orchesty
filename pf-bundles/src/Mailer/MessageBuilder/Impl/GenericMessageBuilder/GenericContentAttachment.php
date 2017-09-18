<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder;

/**
 * Class GenericContentAttachment
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder
 */
class GenericContentAttachment extends GenericAttachmentAbstract
{

    /**
     * @var string
     */
    private $content;

    /**
     * GenericContentAttachment constructor.
     *
     * @param string      $content
     * @param string      $contentType
     * @param null|string $filename
     */
    public function __construct(string $content, string $contentType, ?string $filename = NULL)
    {
        parent::__construct($contentType, $filename);

        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

}