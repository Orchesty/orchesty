<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewInvoiceConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DataProvider;

/**
 * Class FakturoidCreateNewInvoiceConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidCreateNewInvoiceConnectorTest extends FakturoidAbstractConnectorTest
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewInvoiceConnector::getName
     *
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
        $this->setApplicationAndMock('fakturacnitest');

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

        $fakturoidCreateNewInvoiceConnector = new FakturoidCreateNewInvoiceConnector();
        $fakturoidCreateNewInvoiceConnector
            ->setSender($sender)
            ->setDb($this->dm);

        return $fakturoidCreateNewInvoiceConnector;
    }

    /**
     * @return FakturoidCreateNewInvoiceConnector
     */
    public function setApplication(): FakturoidCreateNewInvoiceConnector
    {
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $fakturoidConnector = new FakturoidCreateNewInvoiceConnector();
        $fakturoidConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setDb($this->dm)
            ->setApplication($app);

        return $fakturoidConnector;
    }

}
