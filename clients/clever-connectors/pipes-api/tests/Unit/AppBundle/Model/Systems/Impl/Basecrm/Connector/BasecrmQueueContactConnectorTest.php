<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmQueueContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmQueueContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmQueueContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $systemInstall = [
            'user'              => 'user',
            'token'             => 'token',
            'system'            => 'system',
            'synchronised'      => FALSE,
            'encryptedSettings' => CryptManager::encrypt([
                'access_token' => 'hn6465gfb',
            ]),
        ];

        $conn = $this->mockResponses();

        $dtoData = [
            'system_install' => $systemInstall,
            'topology'       => ['name' => 'topology'],
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        $conn->processAction($processDto);
    }

    /**
     * @return BasecrmQueueContactConnector|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockResponses(): BasecrmQueueContactConnector
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->getMockBuilder(CurlManagerInterface::class)->disableOriginalConstructor()
            ->setMethods(['send'])->getMock();

        $test = $this;

        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $dto) use ($test) {
                    $expt = new RequestDto('POST',
                        new Uri('https://api.getbase.com/v2/sync/start'));
                    $expt->setHeaders([
                        'Accept'                => 'application/json',
                        'Content-Type'          => 'application/json',
                        'User-Agent'            => 'Chrome/58.0.3029.96 Safari/537.36',
                        'Authorization'         => 'Bearer hn6465gfb',
                        'X-Basecrm-Device-UUID' => $dto->getHeaders()['X-Basecrm-Device-UUID'],
                    ]);

                    $test->assertEquals($expt, $dto);

                    return new ResponseDto(201, '', $this->getRequest('syncStartResponse.json'), $expt->getHeaders());
                }
            ));

        return new BasecrmQueueContactConnector(
            $this->container->get('systems.basecrm'),
            $this->mockDM(),
            $curl
        );
    }

    /**
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDM(): DocumentManager
    {
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('saveSystemInstall')->willReturn([]);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

}