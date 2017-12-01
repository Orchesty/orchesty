<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoSyncContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use RingCentral\Psr7\Response;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class ZohoSyncContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Contact
 */
final class ZohoSyncContactConnectorTest extends ConnectorTestCaseAbstract
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
     * @return ZohoSyncContactConnector|MockObject
     */
    private function mockResponses(): ZohoSyncContactConnector
    {
        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        $conn = $this->getMockBuilder(ZohoSyncContactConnector::class)->setConstructorArgs([
            $this->container->get('systems.zoho'),
            $this->createMock(CurlSenderFactory::class),
            $this->mockDM(),
            $processCounter,
        ])->setMethods(['fetchData'])->getMock();

        $test = $this;

        $conn->expects($this->at(0))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri('https://crm.zoho.eu/crm/private/json/Contacts/getRecords?authtoken=token&scope=crmapi&fromIndex=0&toIndex=49'));
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
                        new Uri('https://crm.zoho.eu/crm/private/json/Contacts/getRecords?authtoken=token&scope=crmapi&fromIndex=50&toIndex=99'));
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
     * @return DocumentManager|MockObject
     */
    private function mockDM(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setUser('user')
            ->setToken('token')
            ->setSystem('system')
            ->setSettings(['auth_token' => 'token']);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

}