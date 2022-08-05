<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UserTask\Document;

use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class UserTaskMessageTest
 *
 * @package PipesFrameworkTests\Unit\UserTask\Document
 *
 * @covers  \Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage
 */
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

        self::assertEquals('body', $msg->getBody());
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

}
