<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\CustomNode\Imp;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\RequestbinConnector;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class RequestBinConnectorTest
 *
 * @package PipesPhpSdkTests\Integration\CustomNode\Imp
 */
final class RequestBinConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RequestbinConnector
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RequestbinConnector::process
     *
     * @throws Exception
     */
    public function testProcess(): void
    {
        $curl = self::createPartialMock(CurlManager::class, ['send']);
        $curl->expects(self::any())->method('send')->willReturn(new ResponseDto(200, 'test', 'body', []));

        $connector = new RequestbinConnector('www.testUrl.com', $curl);
        $dto       = $connector->process(new ProcessDto());

        self::assertEquals('{}', $dto->getData());
    }

}
