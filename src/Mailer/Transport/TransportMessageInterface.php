<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 5:53 PM
 */

namespace Hanaboso\PipesFramework\Mailer\Transport;

use Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder\GenericContentAttachment;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder\GenericFsAttachment;

/**
 * Interface TransportMessageInterface
 *
 * @package Hanaboso\PipesFramework\Mailer\Transport
 */
interface TransportMessageInterface
{

    /**
     * @return string
     */
    public function getFrom(): string;

    /**
     * @return string
     */
    public function getTo(): string;

    /**
     * @return string
     */
    public function getSubject(): string;

    /**
     * @return mixed
     */
    public function getDataContent();

    /**
     * @param string $content
     *
     * @return mixed
     */
    public function setContent(string $content);

    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @return string
     */
    public function getContentType(): string;

    /**
     * @return null|string
     */
    public function getTemplate(): ?string;

    /**
     * @return GenericContentAttachment[]
     */
    public function getContentAttachments(): array;

    /**
     * @param GenericContentAttachment $contentAttachment
     */
    public function addContentAttachment(GenericContentAttachment $contentAttachment): void;

    /**
     * @return GenericFsAttachment[]
     */
    public function getFileStorageAttachments(): array;

    /**
     * @param GenericFsAttachment $fileStorageAttachment
     */
    public function addFileStorageAttachment(GenericFsAttachment $fileStorageAttachment): void;

}
