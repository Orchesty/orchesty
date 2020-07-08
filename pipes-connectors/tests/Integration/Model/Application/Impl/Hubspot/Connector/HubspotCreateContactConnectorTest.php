<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubSpotApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use Psr\Log\NullLogger;

/**
 * Class HubspotCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot\Connector
 */
final class HubspotCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @var HubSpotApplication
     */
    private HubSpotApplication $app;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector::getId
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector::__construct
     *
     * @throws Exception
     */
    public function testGetId(): void
    {
        self::assertEquals(
            'hub-spot.create-contact',
            $this->createConnector(DataProvider::createResponseDto())->getId()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector::setLogger
     *
     * @throws Exception
     */
    public function testSetLogger(): void
    {
        $this->createConnector(DataProvider::createResponseDto())->setLogger(new NullLogger());
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector::processEvent
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector::processAction
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
            Json::encode(['name' => 'John Doe', 'email' => 'noreply@johndoe.com', 'phone' => '555-555'])
        );

        $res = $this->createConnector(DataProvider::createResponseDto())
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals('{}', $res->getData());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionDuplicitData(): void
    {
        $this->pfd($this->createApplicationInstall());
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user',
            Json::encode(['name' => 'John Doe', 'email' => 'noreply@johndoe.com', 'phone' => '555-555'])
        );

        $ex  = (string) file_get_contents(__DIR__ . '/data/hubspot409Response.json');
        $res = $this->createConnector(
            DataProvider::createResponseDto($ex, 409)
        )
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals($ex, $res->getData());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector::processAction
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

        $this->app = new HubSpotApplication(self::$container->get('hbpf.providers.oauth2_provider'));
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

        return new HubSpotCreateContactConnector($sender, $this->dm);
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(): ApplicationInstall
    {
        $appInstall = DataProvider::getOauth2AppInstall($this->app->getKey());
        $appInstall->setSettings(
            array_merge(
                $appInstall->getSettings(),
                [ApplicationAbstract::FORM => [HubSpotApplication::APP_ID => 'app_id'],]
            )
        );

        return $appInstall;
    }

}
