<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:09 PM
 */

namespace Hanaboso\PipesFramework\Mailer\Transport\Impl;

use Hanaboso\PipesFramework\Mailer\Transport\TransportException;
use Hanaboso\PipesFramework\Mailer\Transport\TransportInterface;
use Hanaboso\PipesFramework\Mailer\Transport\TransportMessageInterface;
use Swift_Mailer;
use Swift_Message;

/**
 * Class SwiftMailerTransport
 *
 * @package Hanaboso\PipesFramework\Mailer\Transport\Impl
 */
class SwiftMailerTransport implements TransportInterface
{

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * SwiftMailerTransport constructor.
     *
     * @param Swift_Mailer $mailer
     */
    public function __construct(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param TransportMessageInterface $messageData
     *
     * @throws TransportException
     */
    public function send(TransportMessageInterface $messageData): void
    {
        $message = (new Swift_Message())
            ->setSubject($messageData->getSubject())
            ->setFrom($messageData->getFrom())
            ->setTo($messageData->getTo());

        $message->setBody($messageData->getContent(), $messageData->getContentType(), 'utf-8');

        if (!$this->mailer->send($message)) {
            throw new TransportException('Message send failed.', TransportException::SEND_FAILED);
        }
    }

}
