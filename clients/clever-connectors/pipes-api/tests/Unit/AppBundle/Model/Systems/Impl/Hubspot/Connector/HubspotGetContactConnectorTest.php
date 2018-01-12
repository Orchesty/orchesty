<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector\HubspotGetContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class HubspotGetContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
final class HubspotGetContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers HubspotGetContactConnector::processEvent()
     */
    public function testProcessEvent(): void
    {
        $dtoData = [
            'objectId'         => 123,
            'subscriptionType' => 'contact.creation',
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        /** @var HubspotGetContactConnector $syncConn */
        $syncConn   = $this->mockSync();
        $resultDto  = $syncConn->processAction($processDto);
        $resultData = json_decode($resultDto->getData(), TRUE);

        self::assertEquals(123, $resultData['vid']);
        self::assertEquals('abc', $resultData['properties']['email']['value']);
        self::assertEquals('def', $resultData['properties']['firstname']['value']);
        self::assertEquals('ghi', $resultData['properties']['lastname']['value']);
    }

    /**
     *
     */
    public function testProcessActionLimit(): void
    {
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode([
                'objectId'         => 123,
                'subscriptionType' => 'contact.creation',
            ]));

        /** @var MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlManagerInterface::class);
        $sender
            ->expects($this->exactly(1))
            ->method('send')
            ->willReturnCallback(function (RequestDto $requestDto): void {
                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(429));
            });

        $conn = new HubspotGetContactConnector($this->mockSystem(), $this->mockDm(), $sender);
        $data = $conn->processAction($processDto);

        $this->assertEquals(1004, $data->getHeader('pf-result-code'));
    }

    /**
     *
     */
    public function testProcessEventFail(): void
    {
        $dtoData = [
            'objectId' => 123,
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        /** @var HubspotGetContactConnector $syncConn */
        $syncConn = $this->mockSync();
        $syncConn->processAction($processDto);
    }

    /**
     *
     */
    public function testProcessEventFail2(): void
    {
        $dtoData = [
            'subscriptionType' => 123,
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        /** @var HubspotGetContactConnector $syncConn */
        $syncConn = $this->mockSync();
        $syncConn->processAction($processDto);
    }

    /**
     *
     */
    public function testProcessEventFail3(): void
    {
        $dtoData = [
            'objectId'         => 123,
            'subscriptionType' => 123,
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::UNKNOWN_SUBSCRIPTION_TYPE);

        /** @var HubspotGetContactConnector $syncConn */
        $syncConn = $this->mockSync();
        $syncConn->processAction($processDto);
    }

    /**
     *
     */
    public function testProcessEventFail4(): void
    {
        $dtoData = [
            'objectId'         => 123,
            'subscriptionType' => 'contact.deletion',
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([CMHeaders::createKey(CMHeaders::RESULT_CODE) => 0])
            ->setData(json_encode($dtoData));

        /** @var HubspotGetContactConnector $syncConn */
        $syncConn   = $this->mockSync();
        $res        = $syncConn->processAction($processDto);
        $resultCode = $res->getHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE));

        self::assertEquals(1003, $resultCode);
    }

    /**
     * @return MockObject|HubspotGetContactConnector
     */
    private function mockSync()
    {
        $contact = [
            'vid'        => 123,
            'properties' => [
                'email'     => ['value' => 'abc'],
                'firstname' => ['value' => 'def'],
                'lastname'  => ['value' => 'ghi'],
            ],
        ];

        $sender = $this->createMock(CurlManagerInterface::class);
        $sender
            ->method('send')
            ->willReturn(new ResponseDto(200, '', json_encode($contact), []));

        $syncConn = new HubspotGetContactConnector($this->mockSystem(), $this->mockDm(), $sender);

        return $syncConn;
    }

    /**
     * @return MockObject|DocumentManager
     */
    private function mockDm()
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('setSyncTime')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->method('getRepository')
            ->willReturn($systemInstall);

        return $dm;
    }

    /**
     * @return MockObject|HubspotSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('https://api.hubapi.com/'));
        $requestDto->setHeaders([
            'Authorization' => 'Bearer ' . 'access_token_asdf',
            'Content-Type'  => 'application/json',
        ]);
        $mock = $this->createMock(HubspotSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}