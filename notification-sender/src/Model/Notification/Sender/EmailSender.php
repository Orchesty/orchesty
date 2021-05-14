<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Sender;

use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

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
     *
     * @throws TransportExceptionInterface
     */
    public function sendEmail(EmailDto $dto, array $settings): void
    {
        $transport = new EsmtpTransport(
            $settings[EmailDto::HOST],
            intval($settings[EmailDto::PORT]),
            $settings[EmailDto::ENCRYPTION] === 'ssl' ? TRUE : NULL,
        );
        $transport->setUsername($settings[EmailDto::USERNAME]);
        $transport->setPassword($settings[EmailDto::PASSWORD]);

        $mailer = new Mailer($transport);

        foreach ($settings[EmailDto::EMAILS] as $email) {
            $mail = (new Email())
                ->from($settings[EmailDto::EMAIL])
                ->to($email)
                ->subject($dto->getSubject())
                ->text($dto->getBody());

            $mailer->send($mail);
        }
    }

}
