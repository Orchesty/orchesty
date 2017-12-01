<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector\BigcommerceSyncCustomerConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
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
 * Class BigcommerceSyncCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceSyncCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     */
    public function testProcessBatch(): void
    {
        $headers[CMHeaders::createKey(CMHeaders::PROCESS_ID)] = '123';

        $loop       = Factory::create();
        $processDto = (new ProcessDto())
            ->setHeaders($headers)
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
     * @return MockObject|BigcommerceSyncCustomerConnector
     */
    private function getConnectorMock()
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        $senderFactory = $this->createMock(CurlSenderFactory::class);

        $progressCounter = $this->createMock(ProgressCounterService::class);
        $progressCounter->method('setTotal')->willReturn(TRUE);

        $connector = $this->getMockBuilder(BigcommerceSyncCustomerConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->getSystemMock(), $documentManager, $senderFactory, $progressCounter])
            ->getMock();

        $connector->expects($this->at(0))
            ->method('fetchData')
            ->will($this->returnCallback(function (CurlSender $sender, RequestDto $dto) {
                $this->assertEquals(
                    new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/customers/count'),
                    $dto->getUri()
                );

                return resolve(new Response(200, [], '{"count":3}'));
            }));

        $connector->expects($this->at(1))
            ->method('fetchData')
            ->will($this->returnCallback(function (CurlSender $sender, RequestDto $dto) {
                $this->assertEquals(
                    new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/customers?page=1&limit=50'),
                    $dto->getUri()
                );

                return resolve(new Response(200, [], $this->getRequest('BigcommerceCustomersQuery.json')));
            }));

        return $connector;
    }

    /**
     * @return MockObject|BigcommerceSystem
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto('GET', new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/')))->setHeaders([
            'X-Auth-Client' => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
            'X-Auth-Token'  => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);

        $system = $this->createMock(BigcommerceSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

}