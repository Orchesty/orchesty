<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UserTask\Document;

use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class UserTaskMessageTest
 *
 * @package PipesFrameworkTests\Unit\UserTask\Document
 */
#[CoversClass(UserTaskMessage::class)]
final class UserTaskMessageTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testDocument(): void
    {
        $msg = new UserTaskMessage();
        $msg->setBody('body')
            ->setHeaders(['a']);

        self::assertSame('body', $msg->getBody());
        self::assertEquals(['a'], $msg->getHeaders());

        $msg->fromArray(
            [
                UserTaskMessage::BODY    => 'b',
                UserTaskMessage::HEADERS => ['b'],
            ],
        );

        self::assertEquals(
            [
                UserTaskMessage::BODY    => 'b',
                UserTaskMessage::HEADERS => ['b'],
            ],
            $msg->toArray(),
        );
    }

    /**
     *
     */
    public function testEditKeepsPulseHeaders(): void
    {
        $msg = (new UserTaskMessage())->setHeaders(
            ['identity' => 'user@example.com', 'node-id' => 'n1', 'reply-id' => 'r1', 'reply-to' => 'http://trace'],
        );

        $msg->fromArray(
            [
                UserTaskMessage::HEADERS => [
                    'node-id'  => 'n2',
                    'reply-to' => 'http://evil',
                    'source'   => 'pulse',
                ],
            ],
        );

        self::assertEquals(
            ['identity' => 'user@example.com', 'node-id' => 'n2', 'reply-id' => 'r1', 'reply-to' => 'http://trace'],
            $msg->getHeaders(),
        );
    }

    /**
     *
     */
    public function testPublicArrayHidesReplyHeaders(): void
    {
        $msg = (new UserTaskMessage())->setBody('b')->setHeaders(
            ['identity' => 'user@example.com', 'reply-id' => 'r1', 'reply-to' => 'http://trace'],
        );

        self::assertSame(
            [UserTaskMessage::BODY => 'b', UserTaskMessage::HEADERS => ['identity' => 'user@example.com']],
            $msg->toPublicArray(),
        );
    }

}
