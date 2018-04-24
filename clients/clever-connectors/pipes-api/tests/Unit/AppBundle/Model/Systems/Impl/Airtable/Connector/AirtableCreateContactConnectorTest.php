<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector\AirtableCreateContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class AirtableCreateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
final class AirtableCreateContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers AirtableCreateContactConnector::processAction()
     */
    public function testProcessAction(): void
    {
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode([
                'fields' => [
                    'Name'  => 'abc',
                    'Email' => 'a@a.com',
                ],
            ]));

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlManagerInterface::class);
        $sender
            ->expects($this->exactly(1))
            ->method('send')
            ->willReturn(new ResponseDto(200, '', $this->getResponseData(), []));

        /** @var MockObject|LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(0))
            ->method('info');

        $conn = new AirtableCreateContactConnector($this->mockSystem(), $this->mockDm(), $sender);
        $conn->setLogger($logger);
        $data = $conn->processAction($processDto);

        $this->assertEquals($this->getResponseData(), $data->getData());
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
                'fields' => [
                    'Name'  => 'abc',
                    'Email' => 'a@a.com',
                ],
            ]));

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlManagerInterface::class);
        $sender
            ->expects($this->exactly(1))
            ->method('send')
            ->willReturnCallback(function (RequestDto $requestDto): void {
                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(429));
            });

        $conn = new AirtableCreateContactConnector($this->mockSystem(), $this->mockDm(), $sender);
        $data = $conn->processAction($processDto);

        $this->assertEquals(1004, $data->getHeader('pf-result-code'));
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|DocumentManager
     */
    private function mockDm()
    {
        $systemInstal = $this->createMock(SystemInstallRepository::class);
        $systemInstal->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var PHPUnit_Framework_MockObject_MockObject|DocumentManager $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstal);

        return $dm;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|AirtableSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('POST', new Uri('http://airtable.com/'));
        $requestDto->setHeaders([]);
        $mock = $this->createMock(AirtableSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

    /**
     * @return string
     */
    private function getResponseData(): string
    {
        return json_encode([
            'records' => [
                [
                    'fields' => [
                        'Name'  => 'abc',
                        'Email' => 'a@a.com',
                    ],
                ],
            ],
        ]);
    }

}