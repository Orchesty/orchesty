<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\HbPFMetricsBundle\Handler;

use Exception;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class MetricsHandlerTest
 *
 * @package PipesFrameworkTests\Unit\HbPFMetricsBundle\Handler
 */
final class MetricsHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var MetricsHandler
     */
    private MetricsHandler $handler;

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testGetTopologyId(): void
    {
        self::expectException(MetricsException::class);
        $this->invokeMethod($this->handler, 'getTopologyById', ['1']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getNodeByTopologyAndNodeId
     *
     * @throws Exception
     */
    public function testGetNodeByTopologyAndNodeId(): void
    {
        self::expectException(MetricsException::class);
        $this->invokeMethod($this->handler, 'getNodeByTopologyAndNodeId', ['1', '2']);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::getContainer()->get('hbpf.metrics.handler.metrics');
    }

}
