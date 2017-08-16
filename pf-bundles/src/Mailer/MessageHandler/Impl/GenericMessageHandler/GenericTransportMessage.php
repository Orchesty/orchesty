<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:22 PM
 */

namespace Hanaboso\PipesFramework\Mailer\MessageHandler\Impl\GenericMessageHandler;

use Hanaboso\PipesFramework\Mailer\Transport\TransportMessageInterface;

/**
 * Class GenericTransportMessage
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageHandler\Impl\GenericMessageHandlerAbstract
 */
class GenericTransportMessage implements TransportMessageInterface
{

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var null|string
     */
    private $template;

    /**
     * @var mixed
     */
    private $dataContent;

    /**
     * GenericTransportMessage constructor.
     *
     * @param string      $from
     * @param string      $to
     * @param string      $subject
     * @param string      $dataContent
     * @param null|string $template
     */
    public function __construct(string $from, string $to, string $subject, string $dataContent,
                                ?string $template = NULL)
    {
        $this->from        = $from;
        $this->to          = $to;
        $this->subject     = $subject;
        $this->dataContent = $dataContent;
        $this->template    = $template;
        if (!$template)
            $this->content = $dataContent;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return array|mixed|string
     */
    public function getDataContent()
    {
        return $this->template ? ['content' => $this->dataContent] : $this->dataContent;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return null|string
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->template ? 'text/html' : 'text/plain';
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

}
