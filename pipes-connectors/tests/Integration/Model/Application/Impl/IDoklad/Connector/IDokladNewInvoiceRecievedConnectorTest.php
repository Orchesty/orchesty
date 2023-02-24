<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\IDoklad\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector\IDokladNewInvoiceRecievedConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockServer\Mock;
use HbPFConnectorsTests\MockServer\MockServer;

/**
 * Class IDokladNewInvoiceRecievedConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\IDoklad\Connector
 */
final class IDokladNewInvoiceRecievedConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @var IDokladApplication
     */
    private IDokladApplication $app;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector\IDokladNewInvoiceRecievedConnector::getName
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals(
            'i-doklad.new-invoice-recieved',
            $this->createConnector(DataProvider::createResponseDto())->getName(),
        );
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     */
    public function testProcessAction(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["i-doklad"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode(DataProvider::getOauth2AppInstall($this->app->getName())->toArray()),
                ),
            ),
        );

        $dataFromFile = File::getContent(__DIR__ . '/newInvoice.json');

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            $dataFromFile,
        );

        $res = $this->createConnector(
            DataProvider::createResponseDto($dataFromFile),
        )
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals($dataFromFile, $res->getData());
    }

    /**
     * @return void
     * @throws OnRepeatException
     * @throws ApplicationInstallException
     * @throws PipesFrameworkException
     */
    public function testProcessActionRequestException(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["i-doklad"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode(DataProvider::getOauth2AppInstall($this->app->getName())->toArray()),
                ),
            ),
        );

        $dataFromFile = File::getContent(__DIR__ . '/newInvoice.json');

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            $dataFromFile,
        );

        self::expectException(OnRepeatException::class);
        $this
            ->createConnector(DataProvider::createResponseDto(), new CurlException())
            ->setApplication($this->app)
            ->processAction($dto);
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     */
    public function testProcessActionRequestLogicException(): void
    {
        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            '{
            "BankId": 1
            }',
        );

        $this->createConnector(
            DataProvider::createResponseDto(
                '{
            "BankId": 1
            }',
            ),
        )
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals('1003', $dto->getHeaders()['result-code']);
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->app = new IDokladApplication(self::getContainer()->get('hbpf.providers.oauth2_provider'));
    }

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return IDokladNewInvoiceRecievedConnector
     */
    private function createConnector(ResponseDto $dto, ?Exception $exception = NULL): IDokladNewInvoiceRecievedConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        $iDokladNewInvoiceRecievedConnector = new IDokladNewInvoiceRecievedConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $iDokladNewInvoiceRecievedConnector
            ->setSender($sender);

        return $iDokladNewInvoiceRecievedConnector;
    }

}
