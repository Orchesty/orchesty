<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector\PipedriveUpdatePersonConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class PipedriveUpdatePersonConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
final class PipedriveUpdatePersonConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @var DocumentManager
     */
    private $dmMock;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->dmMock = $this->mockDm();
    }

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $conn = new PipedriveUpdatePersonConnector(
            $this->container->get('systems.pipedrive'),
            $this->mockCurl(),
            $this->dmMock
        );

        $data = [
            'id'   => 'pid',
            'body' => json_encode([
                'hardhash' => TRUE,
            ]),
        ];

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode($data));

        $conn->processAction($dto);
    }

    /**
     * @param int $status
     *
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(int $status = 200): CurlManagerInterface
    {
        $test = $this;
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->at(0))
            ->method('send')->will($this->returnCallback(function (RequestDto $dto) use ($test, $status) {
                $expt = new RequestDto('PUT', new Uri('https://api.pipedrive.com/v1/persons/pid?api_token=token'));
                $expt->setHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])->setBody(json_encode([
                    'hardhash' => TRUE,
                ]));

                $test->assertEquals($expt, $dto);

                return new ResponseDto($status, '', '', []);
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