<?php declare(strict_types=1);

namespace DemoTests\Integration\Connector;

use Demo\Connector\BatchConnector;
use DemoTests\KernelTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Throwable;

/**
 * Class BatchConnectorTest
 *
 * @package DemoTests\Integration\Connector
 *
 * @covers  \Demo\Connector\BatchConnector
 */
final class BatchConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @var BatchConnector
     */
    private BatchConnector $connector;

    /**
     * @covers \Demo\Connector\BatchConnector::getId
     */
    public function testGetId(): void
    {
        self::assertEquals('batch', $this->connector->getId());
    }

    /**
     * @covers \Demo\Connector\BatchConnector::processBatch
     *
     * @throws Exception
     */
    public function testProcessBatch(): void
    {
        $this->connector->processBatch(
            new ProcessDto(),
            static function (): void {
                self::assertTrue(TRUE);
            },
        )->then(
            static function (): void {
                self::assertTrue(TRUE);
            },
            static function (Throwable $throwable): void {
                self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
            },
        )->wait();
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.batch');
    }

}
