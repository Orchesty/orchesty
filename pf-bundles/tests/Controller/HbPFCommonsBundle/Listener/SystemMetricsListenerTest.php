<?php declare(strict_types=1);

namespace Tests\Controller\HbPFCommonsBundle\Listener;

use Hanaboso\PipesFramework\Commons\Listener\SystemMetricsListener;
use Hanaboso\PipesFramework\Commons\Metrics\SystemMetrics;
use Hanaboso\PipesFramework\Commons\Utils\CurlMetricUtils;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Symfony\Component\HttpFoundation\Request;
use Tests\ControllerTestCaseAbstract;

/**
 * Class SystemMetricsListenerTest
 *
 * @package Tests\Controller\HbPFCommonsBundle\Listener
 */
class SystemMetricsListenerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testListenerWithoutPipesHeader(): void
    {
        $this->client->request('GET', '/nodes/oiz5', [], [], []);

        /** @var Request $request */
        $request = $this->client->getRequest();

        $this->assertArrayNotHasKey(SystemMetricsListener::METRICS_ATTRIBUTES_KEY, $request->attributes->all());
    }

    /**
     *
     */
    public function testListenerWithPipesHeader(): void
    {
        $headers = [
            'HTTP_' . PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID) => 'topoId',
            'HTTP_' . PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => 'correlationId',
            'HTTP_' . PipesHeaders::createKey(PipesHeaders::NODE_ID) => 'nodeId',
        ];
        $this->client->request('GET', '/nodes/oiz5', [], [], $headers);

        /** @var Request $request */
        $request = $this->client->getRequest();

        $this->assertArrayHasKey(SystemMetricsListener::METRICS_ATTRIBUTES_KEY, $request->attributes->all());

        $metrics = $request->attributes->get(SystemMetricsListener::METRICS_ATTRIBUTES_KEY);

        $this->assertInternalType('array', $metrics);
        $this->assertArrayHasKey(CurlMetricUtils::KEY_TIMESTAMP, $metrics);
        $timestamp = $metrics[CurlMetricUtils::KEY_TIMESTAMP];
        $this->assertGreaterThan(SystemMetrics::getCurrentTimestamp() - 5000, $timestamp);
        $this->assertArrayHasKey(CurlMetricUtils::KEY_CPU, $metrics);
        $cpu = $metrics[CurlMetricUtils::KEY_CPU];
        $this->assertGreaterThan(0, $cpu[SystemMetrics::CPU_TIME_USER]);
        $this->assertGreaterThan(0, $cpu[SystemMetrics::CPU_TIME_KERNEL]);
        $this->assertGreaterThan(0, $cpu[SystemMetrics::CPU_START_TIME]);
    }

}