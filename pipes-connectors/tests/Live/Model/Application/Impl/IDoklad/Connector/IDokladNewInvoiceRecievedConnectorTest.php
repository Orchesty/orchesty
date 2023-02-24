<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockServer\Mock;
use HbPFConnectorsTests\MockServer\MockServer;

/**
 * Class IDokladNewInvoiceRecievedConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad\Connector
 */
final class IDokladNewInvoiceRecievedConnectorTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @group live
     * @throws Exception
     */
    public function testSend(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $app = self::getContainer()->get('hbpf.application.i-doklad');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            'token',
            'ae89f69a-44f4-4163-ac98-************',
            'de469040-fc97-4e03-861e-************',
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["i-doklad"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $conn         = self::getContainer()->get('hbpf.connector.i-doklad.new-invoice-recieved');
        $dataFromFile = File::getContent(__DIR__ . '/newInvoice.json');
        $dto          = DataProvider::getProcessDto($app->getName(), 'user', $dataFromFile);
        $conn->processAction($dto);

        self::assertFake();
    }

}
