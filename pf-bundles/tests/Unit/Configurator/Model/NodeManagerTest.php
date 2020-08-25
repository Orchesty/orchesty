<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class NodeManagerTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Model
 */
final class NodeManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\NodeManager
     * @covers \Hanaboso\PipesFramework\Configurator\Model\NodeManager::updateNode
     *
     * @throws Exception
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
     * @covers \Hanaboso\PipesFramework\Configurator\Model\NodeManager::updateNode
     *
     * @throws Exception
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
     * @covers \Hanaboso\PipesFramework\Configurator\Model\NodeManager::updateNode
     *
     * @throws Exception
     */
    public function testUpdateNodeEnabledFail(): void
    {
        $node = new Node();
        $node
            ->setType(TypeEnum::CONNECTOR)
            ->setHandler(HandlerEnum::ACTION)
            ->setEnabled(FALSE);

        $data = ['enabled' => TRUE];

        self::expectException(NodeException::class);
        self::expectExceptionCode(NodeException::DISALLOWED_ACTION_ON_NON_EVENT_NODE);

        $nodeManager = new NodeManager($this->getDmlMock());
        $nodeManager->updateNode($node, $data);
    }

    /**
     * @return DatabaseManagerLocator
     * @throws Exception
     */
    private function getDmlMock(): DatabaseManagerLocator
    {
        $dm = self::createPartialMock(DocumentManager::class, ['flush']);
        $dm->method('flush')->willReturn(TRUE);

        $dml = self::createPartialMock(DatabaseManagerLocator::class, ['getDm']);
        $dml->method('getDm')->willReturn($dm);

        return $dml;
    }

}
