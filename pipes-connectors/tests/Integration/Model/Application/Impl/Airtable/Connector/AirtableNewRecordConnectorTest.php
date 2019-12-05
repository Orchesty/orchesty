<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Airtable\Connector;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector\AirtableNewRecordConnector;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
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
     * @param int  $code
     * @param bool $isValid
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws PipesFrameworkException
     *
     * @dataProvider getDataProvider
     */
    public function testProcessAction(int $code, bool $isValid): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    []
                ),
            ]
        );

        $applicationInstall = new ApplicationInstall();
        $applicationInstall = $applicationInstall->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        BasicApplicationAbstract::TOKEN => self::API_KEY,
                        AirtableApplication::BASE_ID    => self::BASE_ID,
                        AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                    ],
            ]
        );

        $applicationInstall->setUser('user');
        $applicationInstall->setKey('airtable');

        $newRecordFile = file_get_contents(sprintf('%s/Data/newRecord.json', __DIR__), TRUE);

        $this->pf($applicationInstall);
        $airtableNewRecordConnector = $this->setApplication();

        $response = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto(
                'airtable',
                'user',
                (string) $newRecordFile
            )
        );

        $newRecordFileNoFields = file_get_contents(sprintf('%s/Data/newRecordNoFields.json', __DIR__), TRUE);
        $responseNoFields      = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto(
                'airtable',
                'user',
                (string) $newRecordFileNoFields
            )
        );

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        } else {
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        }

        self::assertEquals($responseNoFields->getHeaders()['pf-result-code'], ProcessDto::STOP_AND_FAILED);
    }

    /**
     * @throws ConnectorException
     * @throws DateTimeException
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

}

