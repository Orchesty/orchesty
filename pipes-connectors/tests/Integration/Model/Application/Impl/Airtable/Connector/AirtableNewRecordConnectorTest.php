<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector\AirtableNewRecordConnector;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockCurlMethod;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class AirtableNewRecordConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable\Connector
 */
final class AirtableNewRecordConnectorTest extends KernelTestCaseAbstract
{

    public const string API_KEY    = 'keyfb******LvKNJI';
    public const string BASE_ID    = 'appX**********XpN';
    public const string TABLE_NAME = 'V******.com';

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["airtable"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($this->createApplication(self::API_KEY)->toArray())),
            ),
        );

        $this->mockCurl([new MockCurlMethod(200, 'response200.json', [])]);

        $airtableNewRecordConnector = $this->setApplication();

        $newRecordFile = File::getContent(sprintf('%s/Data/newRecord.json', __DIR__));

        $response = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto('airtable', 'user', $newRecordFile),
        );

        self::assertSuccessProcessResponse($response, 'response200.json');
    }

    /**
     * @throws Exception
     */
    public function testProcessActionNoFields(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["airtable"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($this->createApplication(self::API_KEY)->toArray())),
            ),
        );

        $this->mockCurl([new MockCurlMethod(500, 'response500.json', [])]);

        $airtableNewRecordConnector = $this->setApplication();
        $newRecordFileNoFields      = File::getContent(sprintf('%s/Data/newRecordNoFields.json', __DIR__));
        $response                   = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto('airtable', 'user', $newRecordFileNoFields),
        );

        self::assertFailedProcessResponse($response, 'response500.json');

        self::assertEquals(ProcessDto::STOP_AND_FAILED, $response->getHeaders()['result-code']);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionNoBaseId(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["airtable"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->createApplication()->toArray()])),
            ),
        );

        $airtableNewRecordConnector = $this->setApplication();

        $newRecordFile = File::getContent(sprintf('%s/Data/newRecord.json', __DIR__));

        $response = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto('airtable', 'user', $newRecordFile),
        );

        self::assertFailedProcessResponse($response, 'newRecord.json');

        self::assertEquals(ProcessDtoAbstract::STOP_AND_FAILED, $response->getHeaders()['result-code']);
    }

    /**
     *
     */
    public function testGetName(): void
    {
        $airtableNewRecordConnector = new AirtableNewRecordConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        self::assertSame(
            'airtable_new_record',
            $airtableNewRecordConnector->getName(),
        );
    }

    /**
     * @return mixed[]
     */
    public function getDataProvider(): array
    {
        return [
            [200, TRUE],
            [500, FALSE],
        ];
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
    }

    /**
     * @return AirtableNewRecordConnector
     * @throws Exception
     */
    private function setApplication(): AirtableNewRecordConnector
    {
        /** @var AirtableApplication $app */
        $app = self::getContainer()->get('hbpf.application.airtable');
        /** @var CurlManager $curl */
        $curl                       = self::getContainer()->get('hbpf.transport.curl_manager');
        $airtableNewRecordConnector = new AirtableNewRecordConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $airtableNewRecordConnector
            ->setSender($curl)
            ->setApplication($app);

        return $airtableNewRecordConnector;
    }

    /**
     * @param string|null $baseId
     *
     * @return ApplicationInstall
     */
    private function createApplication(?string $baseId = NULL): ApplicationInstall
    {

        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    AirtableApplication::BASE_ID    => $baseId,
                    AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                    BasicApplicationAbstract::TOKEN => self::API_KEY,
                ],
            ],
        );

        $applicationInstall->setUser('user');
        $applicationInstall->setKey('airtable');

        return $applicationInstall;
    }

}
