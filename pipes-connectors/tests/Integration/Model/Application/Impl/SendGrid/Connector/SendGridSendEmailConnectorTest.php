<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\SendGrid\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class SendGridSendEmailConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\SendGrid\Connector
 */
final class SendGridSendEmailConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @var SendGridApplication
     */
    private SendGridApplication $app;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::getName
     *
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertEquals(
            'send-grid.send-email',
            $this->createConnector(DataProvider::createResponseDto())->getName(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["send-grid"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->createApplicationInstall()->toArray()])),
            ),
        );

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            Json::encode(['email' => 'noreply@johndoe.com', 'name' => 'John Doe', 'subject' => 'Hello, World!']),
        );

        $res = $this->createConnector(DataProvider::createResponseDto())
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals('{}', $res->getData());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionDataException(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["send-grid"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->createApplicationInstall()->toArray()])),
            ),
        );

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
        );

        self::expectException(ConnectorException::class);
        $this
            ->createConnector(DataProvider::createResponseDto(), new CurlException())
            ->setApplication($this->app)
            ->processAction($dto);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionRequestException(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["send-grid"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->createApplicationInstall()->toArray()])),
            ),
        );

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            Json::encode(['email' => 'noreply@johndoe.com', 'name' => 'John Doe', 'subject' => 'Hello, World!']),
        );

        self::expectException(ConnectorException::class);
        $this
            ->createConnector(DataProvider::createResponseDto(), new CurlException())
            ->setApplication($this->app)
            ->processAction($dto);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionException(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["send-grid"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[]'),
            ),
        );

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);
        $this
            ->createConnector(DataProvider::createResponseDto())
            ->setApplication($this->app)
            ->processAction(DataProvider::getProcessDto());
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

        $this->app = new SendGridApplication();
    }

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return SendGridSendEmailConnector
     */
    private function createConnector(ResponseDto $dto, ?Exception $exception = NULL): SendGridSendEmailConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        $sendGridSendEmailConnector = new SendGridSendEmailConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $sendGridSendEmailConnector
            ->setSender($sender);

        return $sendGridSendEmailConnector;
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(): ApplicationInstall
    {
        $appInstall = DataProvider::getBasicAppInstall($this->app->getName());
        $appInstall
            ->setSettings([ApplicationInterface::AUTHORIZATION_FORM => [SendGridApplication::API_KEY => 'key']]);

        return $appInstall;
    }

}
