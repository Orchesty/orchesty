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
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $logger;

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
            $this->logger->error(
                sprintf(
                    'Message send failed: subject: %s, recipient: %s, datetime: %s.',
                    $messageData->getSubject(),
                    $messageData->getTo(),
                    date(DATE_ATOM)
                )
            );
            throw new TransportException('Message send failed.', TransportException::SEND_FAILED);
        }
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}
