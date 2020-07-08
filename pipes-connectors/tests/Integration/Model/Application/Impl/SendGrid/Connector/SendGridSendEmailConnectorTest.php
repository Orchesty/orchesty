<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\SendGrid\Connector;

use Exception;
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
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class SendGridSendEmailConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\SendGrid\Connector
 */
final class SendGridSendEmailConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @var SendGridApplication
     */
    private SendGridApplication $app;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::getId
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::__construct
     *
     * @throws Exception
     */
    public function testGetId(): void
    {
        self::assertEquals('send-grid.send-email', $this->createConnector(DataProvider::createResponseDto())->getId());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::processEvent
     *
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        self::expectException(ConnectorException::class);
        self::expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
        $this->createConnector(DataProvider::createResponseDto())->processEvent(DataProvider::getProcessDto());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector\SendGridSendEmailConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->pfd($this->createApplicationInstall());
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user',
            Json::encode(['email' => 'noreply@johndoe.com', 'name' => 'John Doe', 'subject' => 'Hello, World!'])
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
        $this->pfd($this->createApplicationInstall());
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user'
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
        $this->pfd($this->createApplicationInstall());
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user',
            Json::encode(['email' => 'noreply@johndoe.com', 'name' => 'John Doe', 'subject' => 'Hello, World!'])
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

        return new SendGridSendEmailConnector($this->dm, $sender);
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(): ApplicationInstall
    {
        $appInstall = DataProvider::getBasicAppInstall($this->app->getKey());
        $appInstall
            ->setSettings([ApplicationInterface::AUTHORIZATION_SETTINGS => [SendGridApplication::API_KEY => 'key']]);

        return $appInstall;
    }

}
