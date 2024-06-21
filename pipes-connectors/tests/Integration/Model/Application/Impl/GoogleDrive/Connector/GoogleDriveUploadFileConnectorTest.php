<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\GoogleDrive\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector\GoogleDriveUploadFileConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class GoogleDriveUploadFileConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\GoogleDrive\Connector
 */
#[CoversClass(GoogleDriveUploadFileConnector::class)]
final class GoogleDriveUploadFileConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @var GoogleDriveApplication
     */
    private GoogleDriveApplication $app;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertEquals(
            'google-drive.upload-file',
            $this->createConnector(DataProvider::createResponseDto())->getName(),
        );
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["google-drive"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode([DataProvider::getOauth2AppInstall($this->app->getName())->toArray()]),
                ),
            ),
        );

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            Json::encode(['name' => 'John Doe', 'email' => 'noreply@johndoe.com', 'phone' => '555-555']),
        );

        $res = $this->createConnector(
            DataProvider::createResponseDto(
                '{"kind": "drive#file","id": "169PQAadbK5TMmuCcZd5aFzZa1sblBymt","name": "my.txt","mimeType": "text/plain"}',
            ),
        )
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals(
            '{"kind": "drive#file","id": "169PQAadbK5TMmuCcZd5aFzZa1sblBymt","name": "my.txt","mimeType": "text/plain"}',
            $res->getData(),
        );
    }

    /**
     * @throws Exception
     */
    public function testProcessActionRequestException(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["google-drive"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode([DataProvider::getOauth2AppInstall($this->app->getName())->toArray()]),
                ),
            ),
        );

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            Json::encode([
                             'email' => 'noreply@johndoe.com',
                             'name'  => 'John Doe',
                             'phone' => '555-555',
                         ]),
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

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->app = new GoogleDriveApplication(self::getContainer()->get('hbpf.providers.oauth2_provider'));
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

        $googleDriveUploadFileConnector = new GoogleDriveUploadFileConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $googleDriveUploadFileConnector
            ->setSender($sender);

        return $googleDriveUploadFileConnector;
    }

}
