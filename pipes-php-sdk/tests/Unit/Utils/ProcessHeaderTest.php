<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Utils;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\System\PipesHeaders;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ProcessHeaderTest
 *
 * @package PipesPhpSdkTests\Unit\Utils
 */
final class ProcessHeaderTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessHeaderTrait::setHeader
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessHeaderTrait::getHeaderByKey

     * @throws Exception
     */
    public function testGetHeaderByKey(): void
    {
        $process = new NullProcessHeader();
        $process = $this->invokeMethod(
            $process,
            'getHeaderByKey',
            [(new ProcessDto()), 'key', [PipesHeaders::createKey('key') => 'data']],
        );

        self::assertEquals('data', $process);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessHeaderTrait::getHeaderByKey

     * @throws Exception
     */
    public function testGetHeaderByKeyErr(): void
    {
        $process = new NullProcessHeader();

        self::expectException(ConnectorException::class);
        $this->invokeMethod($process, 'getHeaderByKey', [new ProcessDto(), 'key']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessHeaderTrait::setHeader

     * @throws Exception
     */
    public function testSetHeader(): void
    {
        $dto     = new ProcessDto();
        $process = new NullProcessHeader();

        /** @var ProcessDto $dto */
        $dto = $this->invokeMethod($process, 'setHeader', [$dto, 'key', 'data']);

        self::assertEquals(['pf-key' => 'data'], $dto->getHeaders());
    }

}
