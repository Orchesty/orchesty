<?php declare(strict_types=1);

namespace Tests\Unit\Configurator\Utils;

use Exception;
use Hanaboso\PipesFramework\Configurator\Utils\CronUtils;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Tests\KernelTestCaseAbstract;

/**
 * Class CronUtilsTest
 *
 * @package Tests\Unit\Configurator\Utils
 */
final class CronUtilsTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetTopologyUrl(): void
    {
        $topology = new Topology();
        $topology->setName('id-1');
        $this->setProperty($topology, 'id', 'test');
        $node = new Node();
        $node->setName('test');
        $this->setProperty($node, 'id', 'id-1');

        self::assertEquals(
            '/topologies/test/nodes/id-1/run',
            CronUtils::getTopologyUrl($topology, $node)
        );
    }

}
