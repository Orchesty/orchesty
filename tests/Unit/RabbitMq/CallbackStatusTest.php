<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 15:03
 */

namespace Tests\Unit\RabbitMq;

use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use PHPUnit\Framework\TestCase;

/**
 * Class CallbackStatusTest
 *
 * @package Tests\Unit\RabbitMq
 */
class CallbackStatusTest extends TestCase
{

    /**
     * @dataProvider getCallbackStatus
     * @covers       CallbackStatus
     *
     * @param int    $status
     * @param string $message
     *
     * @return void
     */
    public function testCallbackStatus(int $status, string $message): void
    {
        $callbackStatus = new CallbackStatus($status, $message);
        $this->assertEquals($status, $callbackStatus->getStatus());
        $this->assertEquals($message, $callbackStatus->getStatusMessage());
    }

    /**
     * @return array
     */
    public function getCallbackStatus(): array
    {
        return [
            [CallbackStatus::SUCCESS, 'test message'],
            [CallbackStatus::FAILED, ''],
        ];
    }

}
