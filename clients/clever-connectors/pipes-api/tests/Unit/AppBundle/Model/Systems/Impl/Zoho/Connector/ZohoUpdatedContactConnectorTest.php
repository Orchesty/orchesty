<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoUpdatedContactConnector;
use DateTime;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class ZohoUpdatedContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Contact
 */
final class ZohoUpdatedContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @var DateTime
     */
    private $lastTime;

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
                'auth_token' => 'token',
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
     * @return ZohoUpdatedContactConnector|MockObject
     */
    private function mockResponses(): ZohoUpdatedContactConnector
    {
        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        $conn = $this->getMockBuilder(ZohoUpdatedContactConnector::class)->setConstructorArgs([
            $this->ownContainer->get('systems.zoho'),
            $this->createMock(CurlSenderFactory::class),
            $this->mockLastSync(),
            $processCounter,
        ])->setMethods(['fetchData'])->getMock();

        $test = $this;

        $conn->expects($this->at(0))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri(sprintf(
                            'https://crm.zoho.eu/crm/private/json/Contacts/getRecords?authtoken=token&scope=crmapi&fromIndex=0&toIndex=49&lastModifiedTime=%s',
                            $this->lastTime->format('Y-m-d+H:i:s')
                        )));
                    $expt->setHeaders([
                        'Content-Type' => 'application/json',
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('updatePage.json')));
                }
            ));

        $conn->expects($this->at(1))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri(sprintf(
                            'https://crm.zoho.eu/crm/private/json/Contacts/getRecords?authtoken=token&scope=crmapi&fromIndex=50&toIndex=99&lastModifiedTime=%s',
                            $this->lastTime->format('Y-m-d+H:i:s')
                        )));
                    $expt->setHeaders([
                        'Content-Type' => 'application/json',
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('contactEmptyPage.json')));
                }
            ));

        return $conn;
    }

    /**
     * @return LastSyncManager|MockObject
     */
    private function mockLastSync(): LastSyncManager
    {
        $this->lastTime = new DateTime('-5 days');

        $last = $this->createMock(LastSyncManager::class);
        $last->method('getLastSync')->willReturn((new LastSync())->setTimestamp($this->lastTime));

        return $last;
    }

}