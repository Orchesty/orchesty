<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidGetAccountDetailConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class FakturoidGetAccountDetailConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidGetAccountDetailConnectorTest extends FakturoidAbstractConnectorTest
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidGetAccountDetailConnector::getName
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals(
            'fakturoid.get-account-detail',
            $this->createConnector(DataProvider::createResponseDto())->getName(),
        );
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockCurl([new MockCurlMethod(200, 'response200.json', [])]);

        $fakturoidGetAccountDetailConnector = $this->setApplicationAndMock('fakturacnitest');

        $response = $fakturoidGetAccountDetailConnector->processAction(
            DataProvider::getProcessDto('fakturoid'),
        );

        self::assertSuccessProcessResponse($response, 'response200.json');
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessActionMissingHeaderValue(): void
    {
        $fakturoidGetAccountDetailConnector = $this->setApplicationAndMockWithoutHeader('fakturacnitest');

        $response = $fakturoidGetAccountDetailConnector->processAction(
            DataProvider::getProcessDto('fakturoid'),
        );
        self::assertEquals('1006', $response->getHeaders()['result-code']);
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return FakturoidGetAccountDetailConnector
     */
    public function createConnector(ResponseDto $dto, ?Exception $exception = NULL): FakturoidGetAccountDetailConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        $fakturoidGetAccountDetailConnector = new FakturoidGetAccountDetailConnector();
        $fakturoidGetAccountDetailConnector
            ->setSender($sender)
            ->setDb($this->dm);

        return $fakturoidGetAccountDetailConnector;
    }

    /**
     * @return FakturoidGetAccountDetailConnector
     */
    public function setApplication(): FakturoidGetAccountDetailConnector
    {
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $fakturoidConnector = new FakturoidGetAccountDetailConnector();
        $fakturoidConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setDb($this->dm)
            ->setApplication($app);

        return $fakturoidConnector;
    }

}
