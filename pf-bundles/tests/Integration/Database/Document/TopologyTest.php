<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Document;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Topology;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyTest
 *
 * @package PipesFrameworkTests\Integration\Database\Document
 */
final class TopologyTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::getVersion
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::setVersion
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::getDescr
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::setDescr
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::getVisibility
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::getStatus
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::setStatus
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::isEnabled
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::getBpmn
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::setBpmn
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::getRawBpmn
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::setRawBpmn
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::getCategory
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::setCategory
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::setContentHash
     * @covers \Hanaboso\PipesFramework\Database\Document\Topology::getContentHash
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
