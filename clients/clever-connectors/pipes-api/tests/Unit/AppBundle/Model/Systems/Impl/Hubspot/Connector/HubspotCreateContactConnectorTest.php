<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector\HubspotCreateContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
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
            $this->container->get('systems.hubspot'),
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