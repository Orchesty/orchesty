<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch;

use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\ErrorMessage;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ErrorMessageTest
 *
 * @package PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch
 */
final class ErrorMessageTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\ErrorMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\ErrorMessage::getCode
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\ErrorMessage::getMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\ErrorMessage::setMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\ErrorMessage::getDetail
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\ErrorMessage::setDetail
     */
    public function testErrorMessage(): void
    {
        $message = new ErrorMessage(400);
        $message->setDetail('detail')->setMessage('message');

        self::assertEquals(400, $message->getCode());
        self::assertEquals('detail', $message->getDetail());
        self::assertEquals('message', $message->getMessage());
    }

}
