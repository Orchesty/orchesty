<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder;

/**
 * Class GenericFsAttachment
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder
 */
class GenericFsAttachment extends GenericAttachmentAbstract
{

    /**
     * @var string
     */
    private $id;

    /**
     * GenericFsAttachment constructor.
     *
     * @param string      $id
     * @param string      $contentType
     * @param null|string $filename
     */
    public function __construct(string $id, string $contentType, ?string $filename = NULL)
    {
        parent::__construct($contentType, $filename);

        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}