<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\BasecrmSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmContactConnector;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class BasecrmContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
class BasecrmContactConnectorTest extends ConnectorTestCaseAbstract
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
                'access_token' => 'sdgfd6g465g46f456f',
                'sync_uuid'    => 'asdgdf546s45gfs6',
            ]),
        ];

        $conn = $this->mockResponses();

        $dtoData = [
            'system_install' => $systemInstall,
            'topology'       => ['name' => 'topology'],
        ];

        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        $data = $conn->processBatch($processDto, $loop, function (): void {
        });

        $data->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();

    }

    /**
     * @return BasecrmContactConnector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockResponses(): BasecrmContactConnector
    {
        $conn = $this->getMockBuilder(BasecrmContactConnector::class)->setConstructorArgs([
            $this->mockSystem(),
            $this->createMock(CurlSenderFactory::class),
        ])->setMethods(['fetchData'])->getMock();

        $test = $this;

        $conn->expects($this->at(0))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri('https://api.getbase.com/v2/sync/64007dee-2651-458c-9699-b0a5bfd32367/queues/main'));
                    $expt->setHeaders([
                        'Accept'                => 'application/json',
                        'Content-Type'          => 'application/json',
                        'User-Agent'            => 'asd',
                        'Authorization'         => 'Bearer sdgfd6g465g46f456f',
                        'X-Basecrm-Device-UUID' => $dto->getHeaders()['X-Basecrm-Device-UUID'],
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('syncPage1.json')));
                }
            ));

        $conn->expects($this->at(1))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri('https://api.getbase.com/v2/sync/64007dee-2651-458c-9699-b0a5bfd32367/queues/main'));
                    $expt->setHeaders([
                        'Accept'                => 'application/json',
                        'Content-Type'          => 'application/json',
                        'User-Agent'            => 'asd',
                        'Authorization'         => 'Bearer sdgfd6g465g46f456f',
                        'X-Basecrm-Device-UUID' => $dto->getHeaders()['X-Basecrm-Device-UUID'],
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('syncPage2.json')));
                }
            ));

        $conn->expects($this->at(2))
            ->method('fetchData')->willReturn(
                resolve(new Response(204, [], ''))
            );

        return $conn;
    }

    /**
     * @return BasecrmSystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockSystem(): BasecrmSystem
    {
        $_SERVER['HTTP_USER_AGENT'] = 'asd';
        $test                       = $this;
        $curl                       = $this->createMock(CurlManagerInterface::class);

        $curl->expects($this->at(0))
            ->method('send')->will($this->returnCallback(
                function (RequestDto $dto) use ($test) {
                    $expt = new RequestDto('POST', new Uri('https://api.getbase.com/v2/sync/start'));
                    $expt->setHeaders([
                        'Accept'        => 'application/json',
                        'Content-Type'  => 'application/json',
                        'User-Agent'    => 'asd',
                        'Authorization' => 'Bearer sdgfd6g465g46f456f',
                        'X-Basecrm-Device-UUID' => $dto->getHeaders()['X-Basecrm-Device-UUID'],
                    ]);

                    $test->assertEquals($expt, $dto);

                    return new ResponseDto(201, '', $this->getRequest('syncStartResponse.json'), []);
                }
            ));

        $system = new BasecrmSystem(
            $this->createMock(DocumentManager::class),
            $curl
        );

        return $system;
    }

}