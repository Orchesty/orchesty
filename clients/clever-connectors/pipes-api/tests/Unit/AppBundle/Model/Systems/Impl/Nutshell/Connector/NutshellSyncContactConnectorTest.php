<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector\NutshellSyncContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\NutshellSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class NutshellSyncContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
final class NutshellSyncContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $loop       = Factory::create();
        $processDto = (new ProcessDto())
            ->setHeaders([])
            ->setData(Json::encode(['data' => ['system_install' => []], ['settings' => [], 'user' => '123']]));

        $this->getConnectorMock()->processBatch($processDto, $loop, function (): void {

        })->then(
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
     * @return MockObject|NutshellSyncContactConnector
     */
    private function getConnectorMock()
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        $senderFactory = $this->createMock(CurlSenderFactory::class);

        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        $connector = $this->getMockBuilder(NutshellSyncContactConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->getSystemMock(), $documentManager, $senderFactory, $processCounter])
            ->getMock();

        $connector->expects($this->at(0))
            ->method('fetchData')
            ->will($this->returnCallback(function (CurlSender $sender, RequestDto $dto) {
                $this->assertEquals(new Uri('https://app.nutshell.com/api/v1/json'), $dto->getUri());
                $this->assertEquals(
                    '{"jsonrpc":"2.0","method":"findContacts","params":{"limit":50,"page":1},"id":"id"}',
                    $dto->getBody()
                );

                return resolve(new Response(200, [], $this->getRequest('NutshellContactQuery.json')));
            }));

        for ($i = 1; $i < 4; $i++) {
            $connector->expects($this->at($i))
                ->method('fetchData')
                ->will($this->returnCallback(function (CurlSender $sender, RequestDto $dto) use ($i) {
                    $this->assertEquals(new Uri('https://app.nutshell.com/api/v1/json'), $dto->getUri());
                    $this->assertEquals(
                        sprintf('{"jsonrpc":"2.0","method":"getContact","params":{"contactId":%s},"id":"email"}', $i),
                        $dto->getBody()
                    );

                    return resolve(new Response(200, [], $this->getRequest('NutshellSingleContactItem.json')));
                }));
        }

        return $connector;
    }

    /**
     * @return MockObject|NutshellSystem
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto('GET', new Uri('https://app.nutshell.com/api/v1/json')))->setHeaders([
            'Authorization' => 'Basic bnV0c2hlbGxAbWFpbGluYXRvci5jb206OTY3YjFmN2IzMjFlNjMwNWQxOGU2NjU2YTY1MGMzMjQyMGFiYTk4ZA==',
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);

        $system = $this->createMock(NutshellSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

}