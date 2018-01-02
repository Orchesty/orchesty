<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector\AirtableUpdateContactConnector;
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

        $conn = $this->container->get('hbpf.connector.airtable-update-contact-connector');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionMessage('Missing data or required field id.');

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testProcess(): void
    {
        $conn = new AirtableUpdateContactConnector(
            new AirtableSystem(),
            $this->mockDm(),
            $this->mockCurl()
        );

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

        $conn->processAction($dto);
    }

    /**
     * @return CurlManagerInterface
     */
    private function mockCurl(): CurlManagerInterface
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);

        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto): ResponseDto {
                    $expt = new RequestDto('POST', new Uri('http://someTable/someId'));
                    $expt->setHeaders([
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer someKey',
                    ])->setBody(json_encode([
                        'fields' => [
                            'air_usubscribe' => TRUE,
                        ],
                    ]));

                    return new ResponseDto(200, '', '', []);
                }
            ));

        return $curl;
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

}