<?php declare(strict_types=1);

namespace DemoTests\Integration\Connector;

use Demo\Connector\BatchConnector;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use React\EventLoop\Factory;
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
     */
    public function testProcessBatch(): void
    {
        $loop = Factory::create();

        $this->connector->processBatch(
            new ProcessDto(),
            $loop,
            static function (): void {
                self::assertTrue(TRUE);
            }
        )->then(
            static function () use ($loop): void {
                self::assertTrue(TRUE);

                $loop->stop();
            },
            static function (Throwable $throwable) use ($loop): void {
                $loop->stop();

                self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
            }
        );

        $loop->run();
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
