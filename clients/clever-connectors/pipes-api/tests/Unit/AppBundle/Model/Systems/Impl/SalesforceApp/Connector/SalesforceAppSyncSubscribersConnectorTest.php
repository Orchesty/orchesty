<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAppSyncSubscribersConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use function React\Promise\resolve;

/**
 * Class SalesforceAppSyncSubscribersConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
final class SalesforceAppSyncSubscribersConnectorTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testProcessBatch(): void
    {
        $dtoData    = [SalesforceAppSystem::DL_ID => '123', SalesforceAppSystem::FILTER_ID => '123'];
        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        /** @var SalesforceAppSyncSubscribersConnector|MockObject $syncConn */
        $syncConn = $this->mockSync();
        $data     = $syncConn->processBatch($processDto, $loop, function (): void {
        });

        $data->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
    }

    /**
     * @throws Exception
     */
    public function testProcessBatchFailed(): void
    {
        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode([]));

        /** @var SalesforceAppSyncSubscribersConnector|MockObject $syncConn */
        $syncConn = $this->mockSync(TRUE);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $syncConn->processBatch($processDto, $loop, function (): void {
        });
    }

    /**
     * @throws Exception
     */
    public function testProcessBatchFailed2(): void
    {
        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode([SalesforceAppSystem::DL_ID => '123']));

        /** @var SalesforceAppSyncSubscribersConnector|MockObject $syncConn */
        $syncConn = $this->mockSync(TRUE);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $syncConn->processBatch($processDto, $loop, function (): void {
        });
    }

    /**
     * @param bool $asFailed
     *
     * @return MockObject|SalesforceAppSyncSubscribersConnector
     * @throws Exception
     * @throws \ReflectionException
     */
    private function mockSync(bool $asFailed = FALSE)
    {
        $systemInstal = $this->createMock(SystemInstallRepository::class);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstal);

        $sender = $this->createMock(CurlSenderFactory::class);

        $syncConn = $this->getMockBuilder(SalesforceAppSyncSubscribersConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $sender, $dm])
            ->getMock();

        if (!$asFailed) {
            $syncConn->expects($this->at(0))
                ->method('fetchData')
                ->willReturn(resolve(new Response(200, [], json_encode(['totalSize' => 1]))));

            $syncConn->expects($this->at(1))
                ->method('fetchData')
                ->willReturn(resolve(new Response(200, [], json_encode(['records' => [['email' => 'aa@aa.com']]]))));

            $syncConn->expects($this->at(2))
                ->method('fetchData')
                ->willReturn(resolve(new Response(200, [], json_encode(['code' => 'OK']))));
        }

        return $syncConn;
    }

    /**
     * @return MockObject|SalesforceAppSystem
     * @throws Exception
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://salesforce.com/'));
        $requestDto->setHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer token123',
        ]);
        $mock = $this->createMock(SalesforceAppSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}