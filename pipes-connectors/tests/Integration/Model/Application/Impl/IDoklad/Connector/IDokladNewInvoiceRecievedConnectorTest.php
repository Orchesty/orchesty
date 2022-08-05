<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\IDoklad\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector\IDokladNewInvoiceRecievedConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class IDokladNewInvoiceRecievedConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\IDoklad\Connector
 */
final class IDokladNewInvoiceRecievedConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @var IDokladApplication
     */
    private IDokladApplication $app;

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
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->pfd(DataProvider::getOauth2AppInstall($this->app->getName()));
        $this->dm->clear();

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
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessActionRequestException(): void
    {
        $this->pfd(DataProvider::getOauth2AppInstall($this->app->getName()));
        $this->dm->clear();

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
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessActionRequestLogicException(): void
    {
        $this->pfd(DataProvider::getOauth2AppInstall($this->app->getName()));
        $this->dm->clear();

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

        $iDokladNewInvoiceRecievedConnector = new IDokladNewInvoiceRecievedConnector();
        $iDokladNewInvoiceRecievedConnector
            ->setSender($sender)
            ->setDb($this->dm);

        return $iDokladNewInvoiceRecievedConnector;
    }

}
