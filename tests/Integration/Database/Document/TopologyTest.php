<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Document;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Topology;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyTest
 *
 * @package PipesFrameworkTests\Integration\Database\Document
 */
#[CoversClass(Topology::class)]
final class TopologyTest extends DatabaseTestCaseAbstract
{

    /**
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
