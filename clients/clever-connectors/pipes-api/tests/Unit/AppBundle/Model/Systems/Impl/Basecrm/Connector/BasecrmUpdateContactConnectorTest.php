<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmUpdateContactConnector;
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
 * Class BasecrmUpdateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testUpdateConnector(): void
    {
        $data = json_encode([
            'data' => [
                'custom_fields' => [
                    CleverCustomKeysEnum::HARD_BOUNCE => FALSE,
                ],
            ],
        ]);

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE,
        ])->setData(json_encode([
            'id'   => 'someId',
            'body' => $data,
        ]));

        $conn = new BasecrmUpdateContactConnector(
            $this->container->get('systems.basecrm'),
            $this->mockDm(),
            $this->mockCurl($data)
        );

        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'access_token' => 'someToken',
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
     * @param string $data
     *
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(string $data): CurlManagerInterface
    {
        $test = $this;

        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) use ($test, $data) {
                $expt = new RequestDto('PUT', new Uri('https://api.getbase.com/v2/contacts/someId'));
                $expt->setBody($data)
                    ->setHeaders([
                        'Accept'        => 'application/json',
                        'Content-Type'  => 'application/json',
                        'User-Agent'    => 'Chrome/58.0.3029.96 Safari/537.36',
                        'Authorization' => 'Bearer someToken',
                    ]);
                $test->assertEquals($expt, $requestDto);

                return new ResponseDto(200, '', $this->getRequest('contactCreated.json'), []);
            }));

        return $curl;
    }

}