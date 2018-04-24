<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 17.9.17
 * Time: 14:19
 */

namespace Tests\Unit\User\Model\Messages;

use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Messages\ActivateMessage;
use Hanaboso\PipesFramework\User\Model\MessageSubject;
use PHPUnit\Framework\TestCase;

/**
 * Class ActivateMessageTest
 *
 * @package Tests\Unit\User\Model\Messages
 */
class ActivateMessageTest extends TestCase
{

    /**
     * @covers ActivateMessage::getMessage()
     */
    public function testGetMessage(): void
    {
        $tkn = new Token();

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getToken')->willReturn($tkn);
        $message = new ActivateMessage($user);
        $this->assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_ACTIVATE,
                'content'     => '',
                'dataContent' => ['link' => $tkn->getHash()],
                'template'    => '',
                'from'        => '',
            ], $message->getMessage()
        );
    }

}
