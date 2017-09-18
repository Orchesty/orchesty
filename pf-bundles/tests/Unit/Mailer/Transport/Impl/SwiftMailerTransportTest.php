<?php declare(strict_types=1);

namespace Tests\Unit\Mailer\Transport\Impl;

use Hanaboso\PipesFramework\Commons\FileStorage\Document\File;
use Hanaboso\PipesFramework\Commons\FileStorage\Dto\FileStorageDto;
use Hanaboso\PipesFramework\Commons\FileStorage\FileStorage;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder\GenericContentAttachment;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder\GenericFsAttachment;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder\GenericTransportMessage;
use Hanaboso\PipesFramework\Mailer\Transport\Impl\SwiftMailerTransport;
use Monolog\Logger;
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
        /** @var PHPUnit_Framework_MockObject_MockObject|Swift_Mailer $fakeMailer */
        $fakeMailer = $this->createPartialMock(Swift_Mailer::class, ['send']);
        $fakeMailer->method('send')->willReturn(1);

        /** @var PHPUnit_Framework_MockObject_MockObject|Logger $logger */
        $logger = $this->createPartialMock(Logger::class, ['info']);
        $logger->method('info')->willReturn(1);

        $attach1 = new GenericContentAttachment('hello', 'text/plain', 'hello.txt');
        $attach2 = new GenericFsAttachment('123abc', 'text/plain', 'hello.txt');

        /** @var PHPUnit_Framework_MockObject_MockObject|FileStorage $fakeStorage */
        $fakeStorage = $this->createPartialMock(FileStorage::class, ['getFileDocument', 'getFileStorage']);
        $fakeStorage->method('getFileDocument')->willReturn(new File());
        $fakeStorage->method('getFileStorage')->willReturn(new FileStorageDto(new File(), ''));

        $message = new GenericTransportMessage('no-reply@test.com', 'no-reply@test.com', 'Subject', 'Content');
        $message->addContentAttachment($attach1);
        $message->addFileStorageAttachment($attach2);

        $mailer = new SwiftMailerTransport($fakeMailer, $fakeStorage);
        $mailer->setLogger($logger);
        $mailer->send($message);
    }

}
