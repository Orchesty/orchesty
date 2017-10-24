<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmAcknowledgeContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmAcknowledgeContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmAcknowledgeContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $conn = new BasecrmAcknowledgeContactConnector(
            $this->mockCurl(),
            $this->container->get('systems.basecrm'),
            $this->mockDm()
        );

        $dto = new ProcessDto();
        $dto->setData($this->getRequest('contactItem.json'))
            ->setHeaders([

            ]);
        $conn->processAction($dto);
    }

    /**
     * @return CurlManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $_SERVER['HTTP_USER_AGENT'] = 'asd';
        $test                       = $this;
        $curl                       = $this->createMock(CurlManagerInterface::class);

        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $dto) use ($test) {
                    $expt = new RequestDto('POST', new Uri('https://api.getbase.com/v2/sync/ack'));
                    $expt->setBody(json_encode([
                        'data' => [
                            'ack_keys' => [
                                'create|Contact|187596661|1508401118|16257613280',
                            ],
                        ],
                    ]))
                        ->setHeaders([
                            'Accept'                => 'application/json',
                            'Content-Type'          => 'application/json',
                            'User-Agent'            => 'Chrome/58.0.3029.96 Safari/537.36',
                            'Authorization'         => 'Bearer sdgfd6g465g46f456f',
                            'X-Basecrm-Device-UUID' => 'asdgdf546s45gfs6',
                        ]);

                    $test->assertEquals($expt, $dto);

                    return new ResponseDto(200, '', '', []);
                }
            ));

        return $curl;
    }

    /**
     * @return DocumentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(): DocumentManager
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setUser('user')
            ->setExpires(NULL)
            ->setSystem('system')
            ->setToken('token')
            ->setSettings([
                'access_token' => 'sdgfd6g465g46f456f',
                'sync_uuid'    => 'asdgdf546s45gfs6',
                'que_id'       => 'fgh54h5mzf',
            ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

}