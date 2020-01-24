<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Utils;

use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use PhpAmqpLib\Message\AMQPMessage;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ProcessDtoFactoryTest
 *
 * @package PipesPhpSdkTests\Unit\Utils
 */
final class ProcessDtoFactoryTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory::createFromMessage
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory::createDto
     */
    public function testCreateFromMessage(): void
    {
        $dto = ProcessDtoFactory::createFromMessage(new AMQPMessage('message'));

        self::assertEquals('message', $dto->getData());
    }

}
