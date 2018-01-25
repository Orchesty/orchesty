<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\SystemMetrics;

use CleverConnectors\AppBundle\Model\SystemMetrics\SystemMetricsDto;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * Class SystemMetricsDtoTest
 *
 * @package Tests\Unit\AppBundle\Model\SystemMetrics
 */
final class SystemMetricsDtoTest extends TestCase
{

    /**
     *
     */
    public function testCreate(): void
    {
        $dto = new SystemMetricsDto('system');

        $this->assertEquals(DateTime::createFromFormat('U', '0', new DateTimeZone('UTC')), $dto->getFrom());
        $this->assertGreaterThan(new DateTime('-1 minute', new DateTimeZone('UTC')), $dto->getTo());
    }

}