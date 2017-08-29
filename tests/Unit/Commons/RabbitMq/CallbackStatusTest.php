<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 15:03
 */

namespace Tests\Unit\Commons\RabbitMq;


use Hanaboso\PipesFramework\Commons\RabbitMq\CallbackStatus;
use PHPUnit\Framework\TestCase;

class CallbackStatusTest extends TestCase
{


    /**
     * @dataProvider getCallbackStatus
     * @covers       CallbackStatus
     *
     * @param int    $status
     * @param string $message
     */
    public function testCallbackStatus(int $status, string $message)
    {
        $callbackStatus = new CallbackStatus($status, $message);
        $this->assertEquals($status, $callbackStatus->getStatus());
        $this->assertEquals($message, $callbackStatus->getStatusMessage());
    }

    /**
     * @return array
     */
    public function getCallbackStatus()
    {
        return [
            [CallbackStatus::SUCCESS_DONE, 'test message'],
            [CallbackStatus::FAILED_DONE, ''],
        ];
    }

}
