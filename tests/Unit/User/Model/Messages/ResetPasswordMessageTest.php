<?php
/**
 * Created by PhpStorm.
 * User: sep
 * Date: 17.9.17
 * Time: 14:47
 */

namespace Tests\Unit\User\Model\Messages;

use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Messages\ResetPasswordMessage;
use Hanaboso\PipesFramework\User\Model\MessageSubject;
use PHPUnit\Framework\TestCase;

class ResetPasswordMessageTest extends TestCase
{

    /**
     * @covers ResetPasswordMessage::getMessage()
     */
    public function testGetMessage()
    {
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getUsername')->willReturn('FooTooBoo');

        $message = new ResetPasswordMessage($user);
        $this->assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_RESET_PASSWORD,
                'content'     => '',
                'dataContent' => ['username' => 'FooTooBoo'],
                'template'    => NULL,
            ], $message->getMessage()
        );
    }

}
