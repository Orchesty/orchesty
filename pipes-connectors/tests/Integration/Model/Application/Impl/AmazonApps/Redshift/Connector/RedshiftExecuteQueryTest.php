<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\AmazonApps\Redshift\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector\RedshiftExecuteQueryConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class RedshiftExecuteQueryTest
 *
 * @package Tests\Integration\Model\Application\Impl\AmazonApps\Redshift\Connector
 */
final class RedshiftExecuteQueryTest extends DatabaseTestCaseAbstract
{

    private const KEY  = 'redshift';
    private const USER = 'user';

    /**
     * @var RedshiftExecuteQueryConnector
     */
    private $connector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.redshift_execute_query');
    }

    /**
     * @covers RedshiftExecuteQueryConnector::processAction
     * @throws Exception
     */
    public function testProcessActionInsert(): void
    {
        $dto = (new ProcessDto())
            ->setData((string) json_encode(['result' => [1, 'Some Title']], JSON_THROW_ON_ERROR));

        $mock = $this->createPartialMock(RedshiftExecuteQueryConnector::class, ['processAction']);
        $mock
            ->method('processAction')
            ->willReturn($dto);
        $dto = $mock->processAction($dto);

        self::assertEquals([1, 'Some Title'], json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR)['result']);
    }

    /**
     * @covers CreateS3ObjectConnector::processAction
     * @throws Exception
     */
    public function testProcessActionMissingName(): void
    {
        $dto = (new ProcessDto())
            ->setData((string) json_encode(['content' => 'Content'], JSON_THROW_ON_ERROR))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        self::expectException(ConnectorException::class);
        self::expectExceptionCode(ConnectorException::CONNECTOR_FAILED_TO_PROCESS);
        self::expectExceptionMessage("Connector 'redshift-query': Required parameter 'query' is not provided!");

        $this->connector->processAction($dto);
    }

}
