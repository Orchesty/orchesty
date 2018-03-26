<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Utils;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Enum\MetricsEnum;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class CurlMetricsUtilsTest
 *
 * @package Tests\Unit\Commons\Utils
 */
final class CurlMetricsUtilsTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testCurlMetrics(): void
    {
        $influx = $this->createMock(InfluxDbSender::class);
        $influx->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (array $times, array $data): bool {
                    self::assertGreaterThan(0, $times[MetricsEnum::REQUEST_TOTAL_DURATION_SENT]);
                    //self::assertNotEmpty($data[MetricsEnum::HOST]);

                    return TRUE;
                }
            ));
        $this->container->set('hbpf.influxdb_sender', $influx);

        $manager = $this->container->get('hbpf.transport.curl_manager');
        $dto     = new RequestDto('GET', new Uri('http://google.com'));
        $manager->send($dto);
    }

}