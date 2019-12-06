<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Sender;

use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

/**
 * Class EmailSender
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Sender
 */
final class EmailSender
{

    /**
     * @param EmailDto $dto
     * @param mixed[]  $settings
     */
    public function send(EmailDto $dto, array $settings): void
    {
        $mailer = new Swift_Mailer(
            (new Swift_SmtpTransport(
                $settings[EmailDto::HOST],
                $settings[EmailDto::PORT],
                $settings[EmailDto::ENCRYPTION] === 'null' ? NULL : $settings[EmailDto::ENCRYPTION],
            ))->setUsername($settings[EmailDto::USERNAME])->setPassword($settings[EmailDto::PASSWORD])
        );

        foreach ($settings[EmailDto::EMAILS] as $email) {
            $mailer->send(
                (new Swift_Message($dto->getSubject(), $dto->getBody()))
                    ->setFrom($dto->getFrom())
                    ->setTo($email)
            );
        }
    }

}
