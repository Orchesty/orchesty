<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector\ZendeskSyncUserConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class ZendeskSyncUserConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
final class ZendeskSyncUserConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @var string
     */
    private $auth = '';

    /**
     *
     */
    public function testProcess(): void
    {
        $conn = $this->mockResponses();

        $dtoData = [
            'system_install' => ['user' => 'user'],
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
     * @return ZendeskSyncUserConnector|MockObject
     */
    private function mockResponses(): ZendeskSyncUserConnector
    {
        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        $conn = $this->getMockBuilder(ZendeskSyncUserConnector::class)->setConstructorArgs([
            $this->ownContainer->get('systems.zendesk'),
            $this->createMock(LastSyncManager::class),
            $this->createMock(CurlSenderFactory::class),
            $this->mockDm(),
            $processCounter,
        ])->setMethods(['fetchData'])->getMock();

        $test = $this;

        $conn->expects($this->at(0))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri('https://hbpf.zendesk.com/api/v2/users.json?per_page=50'));
                    $expt->setHeaders([
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                        'Authorization' => $this->auth,
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('syncPageData.json')));
                }
            ));

        return $conn;
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm()
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

}