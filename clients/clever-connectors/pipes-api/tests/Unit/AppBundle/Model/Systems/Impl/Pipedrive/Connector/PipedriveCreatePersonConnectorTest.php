<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector\PipedriveCreatePersonConnector;
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
 * Class PipedriveCreatePersonConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
final class PipedriveCreatePersonConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $conn = new PipedriveCreatePersonConnector(
            $this->container->get('systems.pipedrive'),
            $this->mockCurl(),
            $this->mockDm()
        );

        $data = [
            'id'   => 'pid',
            'body' => json_encode([
                'name'  => 'sore namae',
                'email' => 'eml@eml.com',
            ]),
        ];

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode($data));

        $conn->processAction($dto);
    }

    /**
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $test = $this;
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->at(0))
            ->method('send')->will($this->returnCallback(function (RequestDto $dto) use ($test) {
                $expt = new RequestDto('POST', new Uri('https://api.pipedrive.com/v1/persons?api_token=token'));
                $expt->setHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])->setBody(json_encode([
                    'id'   => 'pid',
                    'body' => json_encode([
                        'name'  => 'sore namae',
                        'email' => 'eml@eml.com',
                    ]),
                ]));

                $test->assertEquals($expt, $dto);

                return new ResponseDto(201, '', '', []);
            }));

        return $curl;
    }

    /**
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            CleverCustomKeysEnum::HARD_BOUNCE => 'hardhash',
            'api_token'                       => 'token',
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

}