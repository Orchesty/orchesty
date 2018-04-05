<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\SyncConnector;

use CleverConnectors\AppBundle\Model\CM\SyncConnector\SyncResultConnector;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class SyncResultConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\SyncConnector
 */
final class SyncResultConnectorTest extends TestCase
{

    /**
     * @covers \CleverConnectors\AppBundle\Model\CM\SyncConnector\SyncResultConnector::processAction()
     *
     * @throws CurlException
     * @throws ConnectorException
     */
    public function testProcessAction(): void
    {
        $dto     = new ProcessDto();
        $content = json_encode(['foo' => 'bar']);
        $dto->setData($content);

        /** @var CurlManagerInterface|MockObject $curl */
        $curl = $this->getMockBuilder(CurlManagerInterface::class)->getMock();
        $curl->method('send')->willReturnCallback(function (RequestDto $req) use ($content, $dto): ResponseDto {
            $this->assertEquals(CurlManager::METHOD_POST, $req->getMethod());
            $this->assertEquals([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ], $req->getHeaders());
            $this->assertEquals($dto->getData(), $req->getBody());

            return new ResponseDto(200, 'OK', json_encode(['message' => 'Success']), []);
        });

        $con    = new SyncResultConnector($curl, 'http://ranger-api');
        $result = $con->processAction($dto);

        $this->assertSame($dto, $result);
        $this->assertEquals($content, $result->getData());
    }

}
