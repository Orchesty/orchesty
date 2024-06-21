<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubSpotApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;
use Psr\Log\NullLogger;

/**
 * Class HubspotCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot\Connector
 */
#[CoversClass(HubSpotCreateContactConnector::class)]
final class HubspotCreateContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @var HubSpotApplication
     */
    private HubSpotApplication $app;

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
            'hub-spot.create-contact',
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
                '/document/ApplicationInstall?filter={"names":["hub-spot"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->createApplicationInstall()->toArray()])),
            ),
        );

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            Json::encode(['name' => 'John Doe', 'email' => 'noreply@johndoe.com', 'phone' => '555-555']),
        );

        $res = $this->createConnector(DataProvider::createResponseDto())
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals('{}', $res->getData());
    }

    /**
     * @throws Exception
     */
    public function testProcessActionDuplicitData(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["hub-spot"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->createApplicationInstall()->toArray()])),
            ),
        );

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            Json::encode(['name' => 'John Doe', 'email' => 'noreply@johndoe.com', 'phone' => '555-555']),
        );

        $ex        = File::getContent(__DIR__ . '/data/hubspot409Response.json');
        $connector = $this->createConnector(
            DataProvider::createResponseDto($ex, 409),
        );
        $connector->setApplication($this->app);
        $connector->setLogger(new NullLogger());
        $res = $connector->processAction($dto);
        self::assertEquals($ex, $res->getData());
    }

    /**
     * @throws Exception
     */
    public function testProcessActionRequestException(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["hub-spot"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->createApplicationInstall()->toArray()])),
            ),
        );

        $dto = DataProvider::getProcessDto(
            $this->app->getName(),
            'user',
            Json::encode(['name' => 'John Doe', 'email' => 'noreply@johndoe.com', 'phone' => '555-555']),
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

        $this->app = new HubSpotApplication(self::getContainer()->get('hbpf.providers.oauth2_provider'));
    }

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return HubSpotCreateContactConnector
     */
    private function createConnector(ResponseDto $dto, ?Exception $exception = NULL): HubSpotCreateContactConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        $hubSpotCreateContactConnector = new HubSpotCreateContactConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $hubSpotCreateContactConnector
            ->setSender($sender);

        return $hubSpotCreateContactConnector;
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(): ApplicationInstall
    {
        $appInstall = DataProvider::getOauth2AppInstall($this->app->getName());
        $appInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => array_merge(
                    $appInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM],
                    [HubSpotApplication::APP_ID => 'app_id'],
                ),
            ],
        );

        return $appInstall;
    }

}
