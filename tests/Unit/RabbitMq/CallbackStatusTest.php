<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq;

use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use PHPUnit\Framework\TestCase;

/**
 * Class CallbackStatusTest
 *
 * @package Tests\Unit\RabbitMq
 */
final class CallbackStatusTest extends TestCase
{

    /**
     * @dataProvider getCallbackStatus
     *
     * @param int    $status
     * @param string $message
     *
     * @return void
     */
    public function testCallbackStatus(int $status, string $message): void
    {
        $callbackStatus = new CallbackStatus($status, $message);
        self::assertEquals($status, $callbackStatus->getStatus());
        self::assertEquals($message, $callbackStatus->getStatusMessage());
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
