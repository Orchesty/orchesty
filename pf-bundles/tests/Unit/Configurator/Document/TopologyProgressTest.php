<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Document;

use DateTime;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class TopologyProgressTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Document
 */
final class TopologyProgressTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::durationInMs
     *
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $start = new DateTime('2022-06-14T09:04:58.789Z');
        $end   = new DateTime('2022-06-14T09:04:59.707Z');

        self::assertEquals(918, TopologyProgress::durationInMs($start, $end));
    }

}
