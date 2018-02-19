<?php declare(strict_types=1);

namespace Tests\Unit\User\Model\Mailer;

use EmailServiceBundle\Handler\MailHandler;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Hanaboso\PipesFramework\User\Entity\Token;
use Hanaboso\PipesFramework\User\Entity\User;
use Hanaboso\PipesFramework\User\Model\Mailer\Mailer;
use Hanaboso\PipesFramework\User\Model\Messages\ActivateMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class MailerTest
 *
 * @package Tests\Unit\User\Model\Mailer
 */
final class MailerTest extends TestCase
{

    /**
     *
     */
    public function testSendSync(): void
    {
        $producer = $this->createMock(AbstractProducer::class);
        $producer
            ->expects($this->never())
            ->method('publish');

        $mailHandler = $this->createMock(MailHandler::class);
        $mailHandler
            ->expects($this->once())
            ->method('send');

        $mailer = new Mailer($producer, $mailHandler, 'from@email.com', FALSE);
        $mailer->send($this->getMessage());
    }

    /**
     *
     */
    public function testSendAsync(): void
    {
        $producer = $this->createMock(AbstractProducer::class);
        $producer
            ->expects($this->once())
            ->method('publish');

        $mailHandler = $this->createMock(MailHandler::class);
        $mailHandler
            ->expects($this->never())
            ->method('send');

        $mailer = new Mailer($producer, $mailHandler, 'from@email.com');
        $mailer->send($this->getMessage());
    }

    /**
     * @return ActivateMessage
     */
    private function getMessage(): ActivateMessage
    {
        $user = new User();
        $user
            ->setToken(new Token())
            ->setEmail('user@email.com');

        $message = new ActivateMessage($user);
        $message->setHost('abc');

        return $message;
    }

}