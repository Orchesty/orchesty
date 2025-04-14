<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidGetAccountDetailConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class FakturoidGetAccountDetailConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector
 */
#[CoversClass(FakturoidGetAccountDetailConnector::class)]
final class FakturoidGetAccountDetailConnectorTest extends FakturoidAbstractTestConnector
{

    /**
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertSame(
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
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $this->mockCurl([new MockCurlMethod(200, 'response200.json', [])]);

        $fakturoidGetAccountDetailConnector = $this->setApplication();

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["fakturoid"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->getApplication('fakturacnitest')->toArray()])),
            ),
        );

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
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["fakturoid"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->getApplication()->toArray()])),
            ),
        );

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

        $fakturoidGetAccountDetailConnector = new FakturoidGetAccountDetailConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $fakturoidGetAccountDetailConnector
            ->setSender($sender);

        return $fakturoidGetAccountDetailConnector;
    }

    /**
     * @return FakturoidGetAccountDetailConnector
     */
    public function setApplication(): FakturoidGetAccountDetailConnector
    {
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $fakturoidConnector = new FakturoidGetAccountDetailConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $fakturoidConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($app);

        return $fakturoidConnector;
    }

}
