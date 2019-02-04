<?php declare(strict_types=1);

namespace Tests\Unit\Configurator\Utils;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Utils\CronUtils;
use Tests\KernelTestCaseAbstract;

/**
 * Class CronUtilsTest
 *
 * @package Tests\Unit\Configurator\Utils
 */
final class CronUtilsTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetTopologyUrl(): void
    {
        $topology = new Topology();
        $topology->setName('topName');
        $node = new Node();
        $node->setName('nodeName');

        self::assertEquals(
            '/topologies/topName/nodes/nodeName/run-by-name',
            CronUtils::getTopologyUrl($topology, $node)
        );
    }

}
