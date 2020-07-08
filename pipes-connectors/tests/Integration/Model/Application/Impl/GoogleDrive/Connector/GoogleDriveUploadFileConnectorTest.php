<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\GoogleDrive\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector\GoogleDriveUploadFileConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class GoogleDriveUploadFileConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\GoogleDrive\Connector
 */
final class GoogleDriveUploadFileConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @var GoogleDriveApplication
     */
    private GoogleDriveApplication $app;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector\GoogleDriveUploadFileConnector::getId
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector\GoogleDriveUploadFileConnector::__construct
     *
     * @throws Exception
     */
    public function testGetId(): void
    {
        self::assertEquals(
            'google-drive.upload-file',
            $this->createConnector(DataProvider::createResponseDto())->getId()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector\GoogleDriveUploadFileConnector::processEvent
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector\GoogleDriveUploadFileConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->pfd(DataProvider::getOauth2AppInstall($this->app->getKey()));
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user',
            Json::encode(['name' => 'John Doe', 'email' => 'noreply@johndoe.com', 'phone' => '555-555'])
        );

        $res = $this->createConnector(
            DataProvider::createResponseDto(
                '{"kind": "drive#file","id": "169PQAadbK5TMmuCcZd5aFzZa1sblBymt","name": "my.txt","mimeType": "text/plain"}'
            )
        )
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals(
            '{"kind": "drive#file","id": "169PQAadbK5TMmuCcZd5aFzZa1sblBymt","name": "my.txt","mimeType": "text/plain"}',
            $res->getData()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector\GoogleDriveUploadFileConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionRequestException(): void
    {
        $this->pfd(DataProvider::getOauth2AppInstall($this->app->getKey()));
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user',
            Json::encode(['name' => 'John Doe', 'email' => 'noreply@johndoe.com', 'phone' => '555-555'])
        );

        self::expectException(OnRepeatException::class);
        $this
            ->createConnector(DataProvider::createResponseDto(), new CurlException())
            ->setApplication($this->app)
            ->processAction($dto);
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

        $this->app = new GoogleDriveApplication(self::$container->get('hbpf.providers.oauth2_provider'));
    }

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return GoogleDriveUploadFileConnector
     */
    private function createConnector(ResponseDto $dto, ?Exception $exception = NULL): GoogleDriveUploadFileConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        return new GoogleDriveUploadFileConnector($this->dm, $sender);
    }

}
