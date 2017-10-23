<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector\HubspotGetContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class HubspotGetContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotGetContactConnectorTest extends KernelTestCaseAbstract
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
        $resultDto  = $syncConn->processEvent($processDto);
        $resultData = json_decode($resultDto->getData(), TRUE);

        self::assertEquals(123, $resultData['vid']);
        self::assertEquals('abc', $resultData['properties']['email']['value']);
        self::assertEquals('def', $resultData['properties']['firstname']['value']);
        self::assertEquals('ghi', $resultData['properties']['lastname']['value']);
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
        $syncConn->processEvent($processDto);
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
        $syncConn->processEvent($processDto);
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
        $syncConn->processEvent($processDto);
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
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::DISALLOWED_SUBSCRIPTION_TYPE);

        /** @var HubspotGetContactConnector $syncConn */
        $syncConn = $this->mockSync();
        $syncConn->processEvent($processDto);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|HubspotGetContactConnector
     */
    private function mockSync()
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('setSyncTime')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->method('getRepository')
            ->willReturn($systemInstall);

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

        $syncConn = new HubspotGetContactConnector($this->mockSystem(), $dm, $sender);

        return $syncConn;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|HubspotSystem
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