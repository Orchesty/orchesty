<?php declare(strict_types=1);

namespace Tests\Unit\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class NodeManagerTest
 *
 * @package Tests\Unit\Configurator\Model
 */
class NodeManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers NodeManager::updateNode()
     */
    public function testUpdateNode(): void
    {
        $node = new Node();
        $node
            ->setName('name')
            ->setType(TypeEnum::CONNECTOR)
            ->setHandler(HandlerEnum::EVENT);

        $data = [
            'name'     => 'test-name',
            'type'     => TypeEnum::MAPPER,
            'topology' => 'topo',
            'handler'  => HandlerEnum::ACTION,
        ];

        $nodeManager = new NodeManager($this->getDmlMock());
        $result      = $nodeManager->updateNode($node, $data);

        self::assertEquals('test-name', $result->getName());
        self::assertEquals($data['type'], $result->getType());
        self::assertEquals($data['handler'], $result->getHandler());
    }

    /**
     * @covers NodeManager::updateNode()
     */
    public function testUpdateNodeEnabled(): void
    {
        $node = new Node();
        $node
            ->setType(TypeEnum::CONNECTOR)
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(FALSE);

        $data = ['enabled' => TRUE];

        $nodeManager = new NodeManager($this->getDmlMock());
        $result      = $nodeManager->updateNode($node, $data);

        self::assertEquals($data['enabled'], $result->isEnabled());
    }

    /**
     * @covers NodeManager::updateNode()
     */
    public function testUpdateNodeEnabledFail(): void
    {
        $node = new Node();
        $node
            ->setType(TypeEnum::CONNECTOR)
            ->setHandler(HandlerEnum::ACTION)
            ->setEnabled(FALSE);

        $data = ['enabled' => TRUE];

        $this->expectException(NodeException::class);
        $this->expectExceptionCode(NodeException::DISALLOWED_ACTION_ON_NON_EVENT_NODE);

        $nodeManager = new NodeManager($this->getDmlMock());
        $nodeManager->updateNode($node, $data);
    }

    /**
     * @return DatabaseManagerLocator
     */
    private function getDmlMock(): DatabaseManagerLocator
    {
        $dm = $this->createPartialMock(DocumentManager::class, ['flush']);
        $dm->method('flush')->willReturn(TRUE);

        /** @var PHPUnit_Framework_MockObject_MockObject|DatabaseManagerLocator $dml */
        $dml = $this->createPartialMock(DatabaseManagerLocator::class, ['getDm']);
        $dml->method('getDm')->willReturn($dm);

        return $dml;
    }

}