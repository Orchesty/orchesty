<?php declare(strict_types=1);

namespace Tests\Unit\Mailer\MessageHandler\Impl;

use Hanaboso\PipesFramework\Mailer\MessageHandler\Impl\GenericMessageHandler;
use Hanaboso\PipesFramework\Mailer\MessageHandler\MessageHandlerException;
use Hanaboso\PipesFramework\Mailer\Transport\TransportMessageInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class GenericMessageHandlerTest
 *
 * @package Tests\Mailer\MessageHandler\Impl
 */
class GenericMessageHandlerTest extends TestCase
{

    /**
     *
     */
    public function testValid(): void
    {
        $data = [
            'from'    => 'no-reply@test.com',
            'to'      => 'no-reply@test.com',
            'subject' => 'Subject',
            'content' => 'Content',
        ];

        $this->assertTrue(GenericMessageHandler::isValid($data));
    }

    /**
     *
     */
    public function testInvalid(): void
    {
        $data = [
            'from'    => 'no-reply&test.com',
            'to'      => 'no-reply@test.com',
            'subject' => 'Subject',
            'content' => 'Content',
        ];

        $this->assertFalse(GenericMessageHandler::isValid($data));
    }

    /**
     *
     */
    public function testBuildTransportMessage(): void
    {
        $data = [
            'from'    => 'no-reply@test.com',
            'to'      => 'no-reply@test.com',
            'subject' => 'Subject',
            'content' => 'Content',
        ];

        $handler = new GenericMessageHandler();
        $message = $handler->buildTransportMessage($data);

        $this->assertInstanceOf(TransportMessageInterface::class, $message);
    }

    /**
     *
     */
    public function testBuildTransportMessageFails(): void
    {
        $data = [
            'from'    => 'invalid$mail',
            'to'      => 'no-reply@test.com',
            'subject' => 'Subject',
            'content' => 'Content',
        ];

        $handler = new GenericMessageHandler();

        $this->expectException(MessageHandlerException::class);
        $this->expectExceptionCode(MessageHandlerException::INVALID_DATA);

        $handler->buildTransportMessage($data);
    }

}
