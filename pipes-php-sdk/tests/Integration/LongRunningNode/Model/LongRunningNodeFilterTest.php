<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Model;

use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use ReflectionException;

/**
 * Class LongRunningNodeFilterTest
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Model
 */
final class LongRunningNodeFilterTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeFilter::filterCols
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeFilter::orderCols
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeFilter::searchableCols
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeFilter::useTextSearch
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeFilter::prepareSearchQuery
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeFilter::setDocument
     *
     * @throws ReflectionException
     */
    public function testNodeFilter(): void
    {
        $nodeFilter = self::$container->get('hbpf.filter.long_running');
        $this->invokeMethod($nodeFilter, 'setDocument');
        $filerCols = $this->invokeMethod($nodeFilter, 'filterCols');

        self::assertEquals(
            [
                'created'      => 'created',
                'updated'      => 'updated',
                'topologyId'   => 'topologyId',
                'topologyName' => 'topologyName',
                'nodeId'       => 'nodeId',
                'nodeName'     => 'nodeName',
                'auditLogs'    => 'auditLogs',
            ],
            $filerCols
        );

        $orderCols = $this->invokeMethod($nodeFilter, 'orderCols');
        self::assertEquals(
            [
                'created'  => 'created',
                'nodeName' => 'nodeName',
            ],
            $orderCols
        );

        $searchCols = $this->invokeMethod($nodeFilter, 'searchableCols');
        self::assertEquals(['auditLogs'], $searchCols);

        $useTextSearch = $this->invokeMethod($nodeFilter, 'useTextSearch');
        self::assertTrue($useTextSearch);
    }

}
