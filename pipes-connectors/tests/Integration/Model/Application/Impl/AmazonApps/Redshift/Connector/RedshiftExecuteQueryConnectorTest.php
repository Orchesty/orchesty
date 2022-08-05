<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\Redshift\Connector;

use Closure;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector\RedshiftExecuteQueryConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use LogicException;
use PgSql\Connection;
use phpmock\phpunit\PHPMock;

/**
 * Class RedshiftExecuteQueryConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\Redshift\Connector
 */
final class RedshiftExecuteQueryConnectorTest extends DatabaseTestCaseAbstract
{

    use PHPMock;

    private const KEY       = 'redshift';
    private const USER      = 'user';
    private const HEADERS   = ['application' => self::KEY, 'user' => self::USER];
    private const NAMESPACE = 'Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector';

    /**
     * @var RedshiftExecuteQueryConnector
     */
    private RedshiftExecuteQueryConnector $connector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::getContainer()->get('hbpf.connector.redshift-execute-query');
    }

    /**
     * @throws Exception
     */
    public function testProcessActionInsert(): void
    {
        self::markTestSkipped('PGMock fails');
        $this->createApplication();
        $this->prepareConnection(fn(): Connection => new Connection(), fn(): bool => TRUE, fn(): array => [1, 'Some Title']);

        $dto = $this->connector->processAction((new ProcessDto())->setData('{"query":""}')->setHeaders(self::HEADERS));

        self::assertEquals([1, 'Some Title'], Json::decode($dto->getData())['result']);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionUpdate(): void
    {
        self::markTestSkipped('PGMock fails');
        $this->createApplication();
        $this->prepareConnection(fn(): Connection => new Connection(), fn(): bool => TRUE, fn(): bool => FALSE, fn(): int => 1);

        $dto = $this->connector->processAction((new ProcessDto())->setData('{"query":""}')->setHeaders(self::HEADERS));

        self::assertEquals(1, Json::decode($dto->getData())['result']);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionException(): void
    {
        self::markTestSkipped('PGMock fails');
        self::assertException(
            ConnectorException::class,
            null,
            "Connector 'redshift-query': Something gone wrong!"
        );

        $this->createApplication();
        $this->prepareConnection(
            fn(): Connection => new Connection(),
            function (): void {
                throw new LogicException();
            },
            fn(): array => [],
            fn(): bool => TRUE,
            fn(): string => 'Something gone wrong!'
        );

        $this->connector->processAction((new ProcessDto())->setData('{"query":""}')->setHeaders(self::HEADERS));
    }

    /**
     * @throws Exception
     */
    public function testProcessActionMissingName(): void
    {
        $dto = (new ProcessDto())
            ->setData(Json::encode(['content' => 'Content']))
            ->setHeaders(self::HEADERS);

        self::expectException(ConnectorException::class);
        self::expectExceptionMessage("Connector 'redshift-query': Required parameter 'query' is not provided!");

        $this->connector->processAction($dto);
    }

    /**
     * @throws Exception
     */
    protected function createApplication(): void
    {
        $application = (new ApplicationInstall())
            ->setKey(self::KEY)
            ->setUser(self::USER)
            ->setSettings(
                [
                    ApplicationInterface::AUTHORIZATION_FORM => [
                        RedshiftApplication::KEY         => 'Key',
                        RedshiftApplication::SECRET      => 'Secret',
                        RedshiftApplication::REGION      => 'eu-central-1',
                        RedshiftApplication::DB_PASSWORD => 'dbPasswd',
                    ],
                    'host'                    => '',
                    'Port'                    => '',
                    'DBName'                  => '',
                    'MasterUsername'          => '',
                    'Address'                 => '',
                    'DbPassword'              => '',
                ]
            );

        $this->pfd($application);
    }

    /**
     * @param Closure|null $connect
     * @param Closure|null $query
     * @param Closure|null $fetchRow
     * @param Closure|null $affectedRows
     * @param Closure|null $lastError
     */
    protected function prepareConnection(
        ?Closure $connect = NULL,
        ?Closure $query = NULL,
        ?Closure $fetchRow = NULL,
        ?Closure $affectedRows = NULL,
        ?Closure $lastError = NULL
    ): void
    {
        if ($connect) {
            $this->getFunctionMock('Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift', 'pg_connect')
                ->expects(self::any())
                ->willReturnCallback($connect);
        }

        if ($query) {
            $this->getFunctionMock(self::NAMESPACE, 'pg_query')
                ->expects(self::any())
                ->willReturnCallback($query);
        }

        if ($fetchRow) {
            $this->getFunctionMock(self::NAMESPACE, 'pg_fetch_row')
                ->expects(self::any())
                ->willReturnCallback($fetchRow);
        }

        if ($affectedRows) {
            $this->getFunctionMock(self::NAMESPACE, 'pg_affected_rows')
                ->expects(self::any())
                ->willReturnCallback($affectedRows);
        }

        if ($lastError) {
            $this->getFunctionMock(self::NAMESPACE, 'pg_last_error')
                ->expects(self::any())
                ->willReturnCallback($lastError);
        }
    }

}
