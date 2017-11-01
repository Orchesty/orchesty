<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector\HubspotUpdateContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class HubspotUpdateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
final class HubspotUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        $conn = new HubspotUpdateContactConnector(
            $this->container->get('systems.hubspot'),
            $this->mockDm(),
            $this->mockCurl()
        );

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
        ])->setData(json_encode([
            'body' => json_encode([
                'properties' => [
                    [
                        'property' => CleverCustomKeysEnum::UNSUBSCRIBE,
                        'value'    => FALSE,
                    ],
                ],
            ]),
            'id'   => '123456',
        ]));

        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
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
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) {
                $url  = new Uri('https://api.hubapi.com/contacts/v1/contact/vid/123456/profile?hapikey=abab4202-0a4b-4099-8b61-fe325790d7cd');
                $expt = new RequestDto('POST', $url);
                $expt->setHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer asd',
                ])->setBody(json_encode([
                    'properties' => [
                        [
                            'property' => CleverCustomKeysEnum::UNSUBSCRIBE,
                            'value'    => FALSE,
                        ],
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                return new ResponseDto(204, '', '', []);
            }));

        return $curl;
    }

}