<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Airtable\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector\AirtableNewRecordConnector;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class AirtableNewRecordConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\Airtable\Connector
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
        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    'response200.json',
                    []
                ),
            ]
        );

        $airtableNewRecordConnector = $this->setApplicationAndMock(self::API_KEY);

        $newRecordFile = file_get_contents(sprintf('%s/Data/newRecord.json', __DIR__), TRUE);

        $response = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto(
                'airtable',
                'user',
                (string) $newRecordFile
            )
        );

        self::assertSuccessProcessResponse(
            $response,
            'response200.json'
        );

    }

    /**
     * @throws Exception
     */
    public function testProcessActionNoFields(): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    500,
                    'response500.json',
                    []
                ),
            ]
        );

        $airtableNewRecordConnector = $this->setApplicationAndMock(self::API_KEY);
        $newRecordFileNoFields      = file_get_contents(sprintf('%s/Data/newRecordNoFields.json', __DIR__), TRUE);
        $response                   = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto(
                'airtable',
                'user',
                (string) $newRecordFileNoFields
            )
        );

        self::assertFailedProcessResponse(
            $response,
            'response500.json'
        );

        self::assertEquals($response->getHeaders()['pf-result-code'], ProcessDto::STOP_AND_FAILED);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionNoBaseId(): void
    {
        $airtableNewRecordConnector = $this->setApplicationAndMock(NULL);

        $newRecordFile = file_get_contents(sprintf('%s/Data/newRecord.json', __DIR__), TRUE);

        $response = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto(
                'airtable',
                'user',
                (string) $newRecordFile
            )
        );

        self::assertFailedProcessResponse(
            $response,
            'newRecord.json'
        );

        self::assertEquals($response->getHeaders()['pf-result-code'], ProcessDto::STOP_AND_FAILED);

    }

    /**
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $airtableNewRecordConnector = $this->setApplication();

        self::expectException(ConnectorException::class);
        $airtableNewRecordConnector->processEvent(
            DataProvider::getProcessDto(
                'airtable',
                'user',
                ''
            )
        );
    }

    /**
     *
     */
    public function testGetId(): void
    {
        $airtableNewRecordConnector = $this->setApplication();
        self::assertEquals(
            'airtable_new_record',
            $airtableNewRecordConnector->getId()
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
     */
    private function setApplication(): AirtableNewRecordConnector
    {
        $app                        = self::$container->get('hbpf.application.airtable');
        $airtableNewRecordConnector = new AirtableNewRecordConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $airtableNewRecordConnector->setApplication($app);

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
        $applicationInstall = $applicationInstall->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        BasicApplicationAbstract::TOKEN => self::API_KEY,
                        AirtableApplication::BASE_ID    => $baseId,
                        AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                    ],
            ]
        );

        $applicationInstall->setUser('user');
        $applicationInstall->setKey('airtable');
        $this->pf($applicationInstall);
        $this->dm->clear();

        return $this->setApplication();
    }

}

