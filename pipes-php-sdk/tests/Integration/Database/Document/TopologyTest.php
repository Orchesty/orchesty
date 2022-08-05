<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Database\Document;

use Exception;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyTest
 *
 * @package PipesPhpSdkTests\Integration\Database\Document
 */
final class TopologyTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::getVersion
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::setVersion
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::getDescr
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::setDescr
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::getVisibility
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::getStatus
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::setStatus
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::isEnabled
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::getBpmn
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::setBpmn
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::getRawBpmn
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::setRawBpmn
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::getCategory
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::setCategory
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::setContentHash
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Topology::getContentHash
     *
     * @throws Exception
     */
    public function testTopology(): void
    {
        $topology = (new Topology())
            ->setVersion(1)
            ->setDescr('Desc.')
            ->setStatus('Starting')
            ->setCategory('category')
            ->setContentHash('hash')
            ->setBpmn(['bpmn' => '1'])
            ->setRawBpmn('bpmn');
        $this->pfd($topology);

        self::assertEquals(1, $topology->getVersion());
        self::assertEquals('Desc.', $topology->getDescr());
        self::assertEquals('Starting', $topology->getStatus());
        self::assertEquals('category', $topology->getCategory());
        self::assertEquals('hash', $topology->getContentHash());
        self::assertEquals(['bpmn' => '1'], $topology->getBpmn());
        self::assertEquals('bpmn', $topology->getRawBpmn());
        self::assertEquals('draft', $topology->getVisibility());
        self::assertFalse($topology->isEnabled());
    }

}
