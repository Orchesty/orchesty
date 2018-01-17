<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Utils;

use Hanaboso\PipesFramework\Commons\Utils\CronUtils;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Tests\KernelTestCaseAbstract;

/**
 * Class CronUtilsTest
 *
 * @package Tests\Unit\Commons\Utils
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
            '/api/topologies/topName/nodes/nodeName/run_by_name',
            CronUtils::getTopologyUrl($topology, $node)
        );
    }

}