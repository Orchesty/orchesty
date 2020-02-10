<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector\Exception;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ConnectorExceptionTest
 *
 * @package PipesPhpSdkTests\Unit\Connector\Exception
 *
 * @covers  \Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException
 */
final class ConnectorExceptionTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException::getProcessDto
     * @covers \Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException::setProcessDto
     */
    public function testException(): void
    {
        $dto = new ProcessDto();

        self::assertEquals($dto, (new ConnectorException())->setProcessDto($dto)->getProcessDto());
    }

}
