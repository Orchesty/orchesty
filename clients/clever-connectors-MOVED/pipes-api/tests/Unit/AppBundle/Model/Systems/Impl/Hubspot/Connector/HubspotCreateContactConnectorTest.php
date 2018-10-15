<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector\HubspotCreateContactConnector;
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
use Tests\ConnectorTestCaseAbstract;

/**
 * Class HubspotCreateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
final class HubspotCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        $conn = new HubspotCreateContactConnector(
            $this->ownContainer->get('systems.hubspot'),
            $this->mockDm(),
            $this->mockCurl()
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            'properties' => [
                [
                    'property' => 'email',
                    'value'    => 'eml@eml.com',
                ],
                [
                    'property' => 'firstname',
                    'value'    => 'first',
                ],
                [
                    'property' => 'lastname',
                    'value'    => 'last',
                ],
            ],
        ]));

        $conn->processAction($dto);
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
                'properties' => [
                    [
                        'property' => 'email',
                        'value'    => 'eml@eml.com',
                    ],
                    [
                        'property' => 'firstname',
                        'value'    => 'first',
                    ],
                    [
                        'property' => 'lastname',
                        'value'    => 'last',
                    ],
                ],
            ]));

        /** @var MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlManagerInterface::class);
        $sender
            ->expects($this->exactly(1))
            ->method('send')
            ->willReturnCallback(function (RequestDto $requestDto): void {
                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(429));
            });

        $conn = new HubspotCreateContactConnector($this->ownContainer->get('systems.hubspot'), $this->mockDm(), $sender);
        $data = $conn->processAction($processDto);

        $this->assertEquals(1004, $data->getHeader('pf-result-code'));
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'app_id'       => 55999,
            'access_token' => 'asd',
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) {
                $url  = new Uri('https://api.hubapi.com/contacts/v1/contact');
                $expt = new RequestDto('POST', $url);
                $expt->setHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer asd',
                ])->setBody(json_encode([
                    'properties' => [
                        [
                            'property' => 'email',
                            'value'    => 'eml@eml.com',
                        ],
                        [
                            'property' => 'firstname',
                            'value'    => 'first',
                        ],
                        [
                            'property' => 'lastname',
                            'value'    => 'last',
                        ],
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                return new ResponseDto(200, '', '', []);
            }));

        return $curl;
    }

}