<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: maca
 * Date: 03.04.17
 * Time: 20:38
 */

namespace Tests\Unit\Mailer;

use Hanaboso\PipesFramework\Mailer\Mailer;
use Hanaboso\PipesFramework\Mailer\MessageHandler\Impl\GenericMessageHandler;
use Hanaboso\PipesFramework\Mailer\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class MailerTest
 *
 * @package Tests\Mailer
 */
class MailerTest extends TestCase
{

    /**
     * @covers Mailer::renderAndSend()
     */
    public function testSend(): void
    {
        /** @var TransportInterface|PHPUnit_Framework_MockObject_MockObject $transport */
        $transport = $this->createPartialMock(TransportInterface::class, ['send']);
        $transport->method('send')->willReturn(1);

        $data = [
            'from'    => 'valid@mail.com',
            'to'      => 'no-reply@test.com',
            'subject' => 'Subject',
            'content' => 'Content',
        ];

        $handler = new GenericMessageHandler();

        $mailer = new Mailer($transport, NULL);
        $mailer->renderAndSend($handler->buildTransportMessage($data));
    }

    /**
     * @covers Mailer::renderAndSendTest()
     */
    public function testSendTest(): void
    {
        /** @var TransportInterface|PHPUnit_Framework_MockObject_MockObject $transport */
        $transport = $this->createPartialMock(TransportInterface::class, ['send']);
        $transport->method('send')->willReturn(1);

        $data = [
            'from'    => 'valid@mail.com',
            'to'      => 'no-reply@test.com',
            'subject' => 'Subject',
            'content' => 'Content',
        ];

        $handler = new GenericMessageHandler();

        $mailer = new Mailer($transport, NULL);
        $mailer->renderAndSendTest($handler->buildTransportMessage($data));
    }

}
