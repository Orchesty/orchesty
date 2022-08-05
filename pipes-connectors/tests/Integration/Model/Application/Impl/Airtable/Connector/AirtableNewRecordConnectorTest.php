<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector\AirtableNewRecordConnector;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class AirtableNewRecordConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable\Connector
 */
final class AirtableNewRecordConnectorTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = 'keyfb******LvKNJI';
    public const BASE_ID    = 'appX**********XpN';
    public const TABLE_NAME = 'V******.com';

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockCurl([new MockCurlMethod(200, 'response200.json', [])]);

        $airtableNewRecordConnector = $this->setApplicationAndMock(self::API_KEY);

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
        $this->mockCurl([new MockCurlMethod(500, 'response500.json', [])]);

        $airtableNewRecordConnector = $this->setApplicationAndMock(self::API_KEY);
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
        $airtableNewRecordConnector = $this->setApplicationAndMock();

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
        $airtableNewRecordConnector = new AirtableNewRecordConnector();
        self::assertEquals(
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
     * @return AirtableNewRecordConnector
     * @throws Exception
     */
    private function setApplication(): AirtableNewRecordConnector
    {
        /** @var AirtableApplication $app */
        $app = self::getContainer()->get('hbpf.application.airtable');
        /** @var CurlManager $curl */
        $curl                       = self::getContainer()->get('hbpf.transport.curl_manager');
        $airtableNewRecordConnector = new AirtableNewRecordConnector();
        $airtableNewRecordConnector
            ->setSender($curl)
            ->setDb($this->dm)
            ->setApplication($app);

        return $airtableNewRecordConnector;
    }

    /**
     * @param string|null $baseId
     *
     * @return AirtableNewRecordConnector
     * @throws Exception
     */
    private function setApplicationAndMock(?string $baseId = NULL): AirtableNewRecordConnector
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationAbstract::TOKEN => self::API_KEY,
                    AirtableApplication::BASE_ID    => $baseId,
                    AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                ],
            ],
        );

        $applicationInstall->setUser('user');
        $applicationInstall->setKey('airtable');
        $this->pfd($applicationInstall);
        $this->dm->clear();

        return $this->setApplication();
    }

}
