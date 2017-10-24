<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector\ZendeskUpdateUserConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use Tests\PrivateTrait;
use function React\Promise\resolve;

/**
 * Class ZendeskUpdateUserConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
final class ZendeskUpdateUserConnectorTest extends ConnectorTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var string
     */
    private $auth = '';

    /**
     * @var DateTime
     */
    private $time;

    /**
     *
     */
    public function testProcess(): void
    {
        $conn = $this->mockResponses();

        $systemInstall = [
            'user'              => 'user',
            'token'             => 'token',
            'system'            => 'system',
            'synchronised'      => FALSE,
            'encryptedSettings' => CryptManager::encrypt([
                'api_token'  => 'fgjkghf564646',
                'domain'     => 'hbpf',
                'user_email' => 'hbpf@mail.com',
            ]),
        ];

        $this->auth = 'Basic ' . base64_encode('hbpf@mail.com/token:fgjkghf564646');

        $dtoData = [
            'system_install' => $systemInstall,
            'topology'       => ['name' => 'topology'],
        ];

        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        /**
         * Non date query
         */
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

        /**
         * Date query
         */
        $this->setProperty($conn, 'lastSyncManager', $this->mockLastSync(TRUE));
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
     * @return ZendeskUpdateUserConnector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockResponses(): ZendeskUpdateUserConnector
    {
        $conn = $this->getMockBuilder(ZendeskUpdateUserConnector::class)->setConstructorArgs([
            $this->container->get('systems.zendesk'),
            $this->mockLastSync(),
            $this->createMock(CurlSenderFactory::class),
            $this->mockDm(),
        ])->setMethods(['fetchData'])->getMock();

        $test = $this;

        $conn->expects($this->at(0))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri('https://hbpf.zendesk.com/api/v2/search?query=type:user'));
                    $expt->setHeaders([
                        'Content-Type'  => 'application/json',
                        'Authorization' => $this->auth,
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('updatePageData.json')));
                }
            ));

        $conn->expects($this->at(1))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $t = rtrim($test->time->format(DateTime::ISO8601), '+0000');

                    $expt = new RequestDto('GET',
                        new Uri(sprintf('https://hbpf.zendesk.com/api/v2/search?query=type:user&updated>%sZ', $t)));
                    $expt->setHeaders([
                        'Content-Type'  => 'application/json',
                        'Authorization' => $this->auth,
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('updatePageData.json')));
                }
            ));

        return $conn;
    }

    /**
     * @return DocumentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setToken('token')
            ->setUser('user')
            ->setSystem('system')
            ->setSynchronized(FALSE)
            ->setSettings([
                'api_token'  => 'fgjkghf564646',
                'domain'     => 'hbpf',
                'user_email' => 'hbpf@mail.com',
            ]);

        $this->auth = 'Basic ' . base64_encode('hbpf@mail.com/token:fgjkghf564646');

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @param bool $time
     *
     * @return LastSyncManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockLastSync(bool $time = FALSE): LastSyncManager
    {
        $last       = $this->createMock(LastSyncManager::class);
        $this->time = new DateTime('-1 days');
        $last->expects($this->at(0))
            ->method('getLastSync')->willReturn($time ? (new LastSync())->setTimestamp($this->time) : new LastSync());

        return $last;
    }

}