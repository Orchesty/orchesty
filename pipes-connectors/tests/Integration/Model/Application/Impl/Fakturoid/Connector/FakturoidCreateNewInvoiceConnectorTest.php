<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewInvoiceConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class FakturoidCreateNewInvoiceConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector
 */
#[CoversClass(FakturoidCreateNewInvoiceConnector::class)]
final class FakturoidCreateNewInvoiceConnectorTest extends FakturoidAbstractTestConnector
{

    /**
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals(
            'fakturoid.create-new-invoice',
            $this->createConnector(DataProvider::createResponseDto())->getName(),
        );
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["fakturoid"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->getApplication('fakturacnitest')->toArray()])),
            ),
        );

        $app          = self::getContainer()->get('hbpf.application.fakturoid');
        $dataFromFile = File::getContent(__DIR__ . '/Data/requestCreateNewInvoice.json');
        $dto          = DataProvider::getProcessDto(
            $app->getName(),
            'user',
            $dataFromFile,
        );

        $res = $this->createConnector(
            DataProvider::createResponseDto($dataFromFile),
        )
            ->setApplication($app)
            ->processAction($dto);
        self::assertEquals($dataFromFile, $res->getData());
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return FakturoidCreateNewInvoiceConnector
     */
    public function createConnector(ResponseDto $dto, ?Exception $exception = NULL): FakturoidCreateNewInvoiceConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        $fakturoidCreateNewInvoiceConnector = new FakturoidCreateNewInvoiceConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $fakturoidCreateNewInvoiceConnector
            ->setSender($sender);

        return $fakturoidCreateNewInvoiceConnector;
    }

    /**
     * @return FakturoidCreateNewInvoiceConnector
     */
    public function setApplication(): FakturoidCreateNewInvoiceConnector
    {
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $fakturoidConnector = new FakturoidCreateNewInvoiceConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $fakturoidConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($app);

        return $fakturoidConnector;
    }

}
