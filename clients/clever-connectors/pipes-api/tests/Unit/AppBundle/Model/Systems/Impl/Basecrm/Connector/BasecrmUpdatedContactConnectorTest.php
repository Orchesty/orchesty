<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmUpdatedContactConnector;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class BasecrmUpdatedContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmUpdatedContactConnectorTest extends ConnectorTestCaseAbstract
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
                'que_id'       => 'gf5h46dg5',
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
     * @return BasecrmUpdatedContactConnector|MockObject
     */
    private function mockResponses(): BasecrmUpdatedContactConnector
    {
        $conn = $this->getMockBuilder(BasecrmUpdatedContactConnector::class)->setConstructorArgs([
            $this->container->get('systems.basecrm'),
            $this->createMock(CurlSenderFactory::class),
        ])->setMethods(['fetchData'])->getMock();

        $test = $this;

        $conn->expects($this->at(0))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri('https://api.getbase.com/v2/sync/gf5h46dg5/queues/main'));
                    $expt->setHeaders([
                        'Accept'                => 'application/json',
                        'Content-Type'          => 'application/json',
                        'User-Agent'            => 'Chrome/58.0.3029.96 Safari/537.36',
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
                        new Uri('https://api.getbase.com/v2/sync/gf5h46dg5/queues/main'));
                    $expt->setHeaders([
                        'Accept'                => 'application/json',
                        'Content-Type'          => 'application/json',
                        'User-Agent'            => 'Chrome/58.0.3029.96 Safari/537.36',
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

}