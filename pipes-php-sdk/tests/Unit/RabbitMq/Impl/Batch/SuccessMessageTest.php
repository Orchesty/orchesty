<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch;

use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\System\PipesHeaders;
use InvalidArgumentException;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class SuccessMessageTest
 *
 * @package PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch
 */
final class SuccessMessageTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage::getSequenceId
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage::getData
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage::setData
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage::getHeaders
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage::addHeader
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage::hasHeader
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage::setResultCode
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage::setMessage
     */
    public function testSuccessMessage(): void
    {
        $message = new SuccessMessage(5);

        $message->setData('data')->setResultCode(202)->setMessage('message')->setResultCode(203);

        self::assertEquals('data', $message->getData());
        self::assertEquals(5, $message->getSequenceId());
        self::assertEquals('message', $message->getHeader(PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE)));
        self::assertEquals(['pf-result-code' => 203, 'pf-result-message' => 'message'], $message->getHeaders());
        self::assertTrue($message->hasHeader(PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE)));

        self::expectException(InvalidArgumentException::class);
        new SuccessMessage(-5);
    }

}
