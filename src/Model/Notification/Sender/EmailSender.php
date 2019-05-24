<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Sender;

use EmailServiceBundle\Exception\MailerException;
use EmailServiceBundle\Mailer\Mailer;
use EmailServiceBundle\MessageBuilder\Impl\GenericMessageBuilder\GenericTransportMessage;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;

/**
 * Class EmailSender
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Sender
 */
final class EmailSender
{

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * EmailSender constructor.
     *
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param EmailDto $dto
     * @param array    $settings
     *
     * @throws MailerException
     */
    public function send(EmailDto $dto, array $settings): void
    {
        foreach ($settings[EmailDto::EMAILS] as $email) {
            $this->mailer->renderAndSend(new GenericTransportMessage(
                $dto->getFrom(),
                $email,
                $dto->getSubject(),
                $dto->getBody()
            ));
        }
    }

}
