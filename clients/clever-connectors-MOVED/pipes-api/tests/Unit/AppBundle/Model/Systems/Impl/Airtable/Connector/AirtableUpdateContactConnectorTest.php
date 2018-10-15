<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector\AirtableUpdateContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class AirtableUpdateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
final class AirtableUpdateContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testMissingId(): void
    {
        $dto = new ProcessDto();
        $dto->setHeaders([])->setData('{}');

        $conn = $this->ownContainer->get('hbpf.connector.airtable-update-contact-connector');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionMessage('Missing data or required field id.');

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testProcess(): void
    {
        $dto = new ProcessDto();
        $dto->setHeaders([
            'pf-table-url' => 'http://someTable',
        ])
            ->setData(json_encode([
                'id'     => 'someId',
                'fields' => [
                    'air_unsubscribe' => TRUE,
                ],
            ]));

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlManagerInterface::class);
        $sender
            ->expects($this->exactly(1))
            ->method('send')
            ->willReturn(new ResponseDto(200, '', $this->getResponseData(), []));

        $conn = new AirtableUpdateContactConnector(new AirtableSystem(), $this->mockDm(), $sender);
        $data = $conn->processAction($dto);
        $this->assertEquals($this->getResponseData(), $data->getData());
    }

    /**
     *
     */
    public function testProcessActionLimit(): void
    {
        $dto = new ProcessDto();
        $dto->setHeaders([
            'pf-table-url' => 'http://someTable',
        ])
            ->setData(json_encode([
                'id'     => 'someId',
                'fields' => [
                    'air_unsubscribe' => TRUE,
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

        $conn = new AirtableUpdateContactConnector(new AirtableSystem(), $this->mockDm(), $sender);
        $data = $conn->processAction($dto);

        $this->assertEquals(1004, $data->getHeader('pf-result-code'));
    }

    /**
     * @return DocumentManager
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            SystemInstall::FORMS => [
                [
                    AirtableSystem::TABLE_URL => 'http://someTable',
                    AirtableSystem::LIST_ID   => 'someList',
                    AirtableSystem::VIEW      => NULL,
                ],
            ],
            'api_key'            => 'someKey',
        ]);

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
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