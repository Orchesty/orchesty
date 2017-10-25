<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Contact;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoDeleteContactConnector;
use DateTime;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class ZohoDeleteContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Contact
 */
final class ZohoDeleteContactConnectorTest extends ConnectorTestCaseAbstract
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
     * @return ZohoDeleteContactConnector|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockResponses(): ZohoDeleteContactConnector
    {
        $conn = $this->getMockBuilder(ZohoDeleteContactConnector::class)->setConstructorArgs([
            $this->container->get('systems.zoho'),
            $this->createMock(CurlSenderFactory::class),
            $this->mockLastSync(),
        ])->setMethods(['fetchData'])->getMock();

        $test = $this;

        $conn->expects($this->at(0))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri(sprintf(
                            'https://crm.zoho.eu/crm/private/json/Contacts/getDeletedRecordIds?authtoken=token&scope=crmapi&fromIndex=0&toIndex=49&lastModifiedTime=%s',
                            $this->lastTime->format('Y-m-d+H:i:s')
                        )));
                    $expt->setHeaders([
                        'Content-Type' => 'application/json',
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('deletedPage.json')));
                }
            ));

        $conn->expects($this->at(1))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri(sprintf(
                            'https://crm.zoho.eu/crm/private/json/Contacts/getDeletedRecordIds?authtoken=token&scope=crmapi&fromIndex=50&toIndex=99&lastModifiedTime=%s',
                            $this->lastTime->format('Y-m-d+H:i:s')
                        )));
                    $expt->setHeaders([
                        'Content-Type' => 'application/json',
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('deletedEmptyPage.json')));
                }
            ));

        return $conn;
    }

    /**
     * @return LastSyncManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockLastSync(): LastSyncManager
    {
        $this->lastTime = new DateTime('-5 days');

        $last = $this->createMock(LastSyncManager::class);
        $last->method('getLastSync')->willReturn((new LastSync())->setTimestamp($this->lastTime));

        return $last;
    }

}