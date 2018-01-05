<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 17.9.17
 * Time: 14:45
 */

namespace Tests\Unit\User\Model\Messages;

use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Messages\RegisterMessage;
use Hanaboso\PipesFramework\User\Model\MessageSubject;
use PHPUnit\Framework\TestCase;

/**
 * Class RegisterMessageTest
 *
 * @package Tests\Unit\User\Model\Messages
 */
class RegisterMessageTest extends TestCase
{

    /**
     * @covers RegisterMessage::getMessage()
     */
    public function testGetMessage(): void
    {
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $message = new RegisterMessage($user);
        $this->assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_REGISTER,
                'content'     => '',
                'dataContent' => [],
                'template'    => '',
            ], $message->getMessage()
        );
    }

}
