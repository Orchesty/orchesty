<?php declare(strict_types=1);

namespace Tests\Unit\Mailer\Transport\Impl;

use Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder\GenericTransportMessage;
use Hanaboso\PipesFramework\Mailer\Transport\Impl\SwiftMailerTransport;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Swift_Mailer;

/**
 * Class SwiftMailerTransportTest
 *
 * @package Hanaboso\PipesFramework\Tests\Mailer\Transport\Impl
 */
class SwiftMailerTransportTest extends TestCase
{

    /**
     *
     */
    public function testSend(): void
    {
        /**
         * @var PHPUnit_Framework_MockObject_MockObject|Swift_Mailer $fakeMailer
         */
        $fakeMailer = $this->createPartialMock(Swift_Mailer::class, ['send']);
        $fakeMailer->method('send')->willReturn(1);

        $message = new GenericTransportMessage('no-reply@test.com', 'no-reply@test.com', 'Subject', 'Content');
        $mailer  = new SwiftMailerTransport($fakeMailer);
        $mailer->send($message);
    }

}
