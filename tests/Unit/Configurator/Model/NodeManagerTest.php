<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Repository\NodeRepository;
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
            ->setType(TypeEnum::CONNECTOR->value)
            ->setHandler(HandlerEnum::EVENT->value);

        $data = [
            'name'     => 'test-name',
            'type'     => TypeEnum::MAPPER->value,
            'topology' => 'topo',
            'handler'  => HandlerEnum::ACTION->value,
        ];

        $nodeManager = new NodeManager($this->getDmlMock(),$this->getCronMock());
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
            ->setType(TypeEnum::CONNECTOR->value)
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(FALSE);

        $data = ['enabled' => TRUE];

        $nodeManager = new NodeManager($this->getDmlMock(),$this->getCronMock());
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
            ->setType(TypeEnum::CONNECTOR->value)
            ->setHandler(HandlerEnum::ACTION->value)
            ->setEnabled(FALSE);

        $data = ['enabled' => TRUE];

        self::expectException(NodeException::class);
        self::expectExceptionCode(NodeException::DISALLOWED_ACTION_ON_NON_EVENT_NODE);

        $nodeManager = new NodeManager($this->getDmlMock(), $this->getCronMock());
        $nodeManager->updateNode($node, $data);
    }

    /**
     * @return DatabaseManagerLocator
     * @throws Exception
     */
    private function getDmlMock(): DatabaseManagerLocator
    {
        $repo = self::createPartialMock(NodeRepository::class, []);
        $dm   = self::createPartialMock(DocumentManager::class, ['flush', 'getRepository']);
        $dm->method('flush')->willReturn(TRUE);
        $dm->method('getRepository')->willReturn($repo);

        $dml = self::createPartialMock(DatabaseManagerLocator::class, ['getDm']);
        $dml->method('getDm')->willReturn($dm);

        return $dml;
    }

    /**
     * @return CronManager
     * @throws Exception
     */
    private function getCronMock(): CronManager
    {
        $cron = self::createPartialMock(CronManager::class, ['upsert']);
        $cron->method('upsert')->willReturn(new ResponseDto(200, '', '', []));

        return $cron;
    }

}
