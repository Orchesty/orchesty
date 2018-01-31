<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\SystemMetrics;

use CleverConnectors\AppBundle\Enum\SystemMetricsIntervalEnum;
use CleverConnectors\AppBundle\Model\SystemMetrics\SystemMetrics;
use CleverConnectors\AppBundle\Model\SystemMetrics\SystemMetricsDto;
use CleverConnectors\AppBundle\Utils\DateTimeUtils;
use Elastica\Client;
use Elastica\Document;
use Tests\KernelTestCaseAbstract;

/**
 * Class SystemMetricsTest
 *
 * @package Tests\Integration\AppBundle\Model\SystemMetrics
 */
final class SystemMetricsTest extends KernelTestCaseAbstract
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var SystemMetrics
     */
    private $manager;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client  = $this->container->get('cc.elastica.client');
        $this->manager = $this->container->get('cc.system_metrics.manager');
    }

    /**
     *
     */
    public function testGetSystemMetrics(): void
    {
        $this->prepareData();
        $this->assertEquals([
            978307200 => 0,
            978310800 => 1,
            978314400 => 0,
            978318000 => 1,
            978321600 => 0,
            978325200 => 1,
            978328800 => 0,
            978332400 => 1,
            978336000 => 0,
            978339600 => 1,
            978343200 => 0,
            978346800 => 1,
            978350400 => 0,
            978354000 => 1,
            978357600 => 0,
            978361200 => 1,
            978364800 => 0,
            978368400 => 1,
            978372000 => 0,
            978375600 => 1,
            978379200 => 0,
            978382800 => 1,
            978386400 => 0,
            978390000 => 1,
        ], $this->manager->getSystemMetrics(new SystemMetricsDto(
            'system',
            DateTimeUtils::getUTCDateTime('01-01-2001'),
            DateTimeUtils::getUTCDateTime('02-01-2001'),
            SystemMetricsIntervalEnum::HOUR
        )));
    }

    /**
     *
     */
    public function testGetSystemRequestCount(): void
    {
        $this->prepareData();
        $this->assertEquals(12, $this->manager->getSystemRequestCount(new SystemMetricsDto(
            'system',
            DateTimeUtils::getUTCDateTime('01-01-2001'),
            DateTimeUtils::getUTCDateTime('02-01-2001'),
            SystemMetricsIntervalEnum::HOUR
        )));
    }

    /**
     *
     */
    private function prepareData(): void
    {
        $index = $this->client->getIndex('index');
        if ($index->exists()) {
            $index->delete();
        }

        $index->create([], TRUE);
        $type = $index->getType('limiter');

        for ($i = 0; $i < 24; $i++) {
            $type->addDocument(new Document($i, [
                'id'         => $i,
                'timestamp'  => (int) DateTimeUtils::getUTCDateTime(sprintf('01-01-2001 +%s hour', $i))->format('U'),
                'system-key' => $i % 2 ? 'system' : 'another-system',
            ]));
        }

        $index->refresh();
    }

}