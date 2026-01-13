<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Model;

use Exception;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyManagerTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Model
 */
#[CoversClass(TopologyManager::class)]
#[CoversClass(Schema::class)]
#[CoversClass(SystemConfigDto::class)]
#[AllowMockObjectsWithoutExpectations]
final class TopologyManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var TopologyManager
     */
    private TopologyManager $manager;

    /**
     * @throws Exception
     */
    public function testCreateTopologyWithSameName(): void
    {
        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS);
        self::assertSame(1, $this->manager->createTopology(['name' => 'Topology'])->getVersion());
        $this->manager->createTopology(['name' => 'Topology'])->getVersion();
    }

    /**
     * @throws Exception
     */
    public function testUpdateUnpublishedTopologyWithName(): void
    {
        $topology = $this->manager->createTopology(['name' => 'Topology']);
        $this->manager->updateTopology($topology, ['name' => 'Another Topology']);

        $this->dm->clear();
        $topologies = $this->dm->getRepository(Topology::class)->findBy(['name' => 'another-topology']);
        self::assertEquals(1, count($topologies));
    }

    /**
     * @throws Exception
     */
    public function testUpdatePublishedTopologyWithName(): void
    {
        $topology = $this->manager->createTopology(['name' => 'Topology']);
        $topology->setVisibility(TopologyStatusEnum::PUBLIC->value);
        $this->dm->flush();

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_CANNOT_CHANGE_NAME);

        $this->manager->updateTopology($topology, ['name' => 'Another Topology']);
    }

    /**
     * @throws Exception
     */
    public function testCheckTopologyNameUnPublished(): void
    {

        $this->manager->createTopology(['name' => 'Another Topology']);
        $topology = $this->manager->createTopology(['name' => 'Topology']);
        self::assertSame(1, $topology->getVersion());

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS);
        $this->manager->updateTopology($topology, ['name' => 'Another Topology']);
    }

    /**
     * @throws Exception
     */
    public function testUpdateTopology(): void
    {
        $top = new Topology();
        $top
            ->setVisibility(TopologyStatusEnum::DRAFT->value)
            ->setDescr('asd')
            ->setName('asdd')
            ->setBpmn(['bpmn'])
            ->setRawBpmn('bpmn')
            ->setEnabled(TRUE);

        $this->dm->persist($top);

        $expt = [
            'bpmn'        => 'fgdgfd',
            'description' => 'desc',
            'enabled'     => FALSE,
            'name'        => 'name',
        ];

        $this->manager->updateTopology($top, $expt);
        $this->dm->clear();
        /** @var Topology $top */
        $top = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $top->getId()]);
        self::assertSame('name', $top->getName());
        self::assertSame('desc', $top->getDescr());
        self::assertEquals(['bpmn'], $top->getBpmn());
        self::assertSame('bpmn', $top->getRawBpmn());
        self::assertFalse($top->isEnabled());
    }

    /**
     * @throws Exception
     */
    public function testCheckTopologyIsSame(): void
    {
        $schema = $this->getSchema();

        $top = new Topology();
        $top
            ->setVisibility(TopologyStatusEnum::DRAFT->value)
            ->setDescr('asd')
            ->setName('asdd')
            ->setEnabled(TRUE);

        $this->dm->persist($top);

        $this->manager->saveTopologySchema($top, '', $schema);
        self::assertTrue($this->manager->checkTopologySchemaIsSame($top, $schema));
    }

    /**
     * @throws Exception
     */
    public function testPublishTopology(): void
    {
        $top = new Topology();
        $top->setName('asd')->setVisibility(TopologyStatusEnum::DRAFT->value);

        $this->dm->persist($top);

        $node = new Node();
        $node
            ->setName('abc')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setTopology($top->getId());

        $this->dm->persist($node);
        $this->dm->flush();

        /** @var Topology $res */
        $res = $this->manager->publishTopology($top);
        self::assertSame(TopologyStatusEnum::PUBLIC->value, $res->getVisibility());
    }

    /**
     * @throws Exception
     */
    public function testPublishTopologyNoNodes(): void
    {
        $top = new Topology();
        $top->setName('asd')->setVisibility(TopologyStatusEnum::DRAFT->value);

        $this->dm->persist($top);
        $this->dm->flush();

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_HAS_NO_NODES);

        $this->manager->publishTopology($top);
    }

    /**
     * @throws Exception
     */
    public function testCloneTopology(): void
    {
        $top = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(FALSE)
            ->setDescr('desc')
            ->setBpmn(['asd'])
            ->setRawBpmn('asd');

        $this->dm->persist($top);
        $this->dm->flush();

        /**
         * 1 -> 2 -> 3
         *        -> 4 -> 5
         */

        $node5 = new Node();
        $node5
            ->setName('node5')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node5')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE);
        $this->dm->persist($node5);

        $node4 = new Node();
        $node4
            ->setName('node4')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node4')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node5));
        $this->dm->persist($node4);

        $node3 = new Node();
        $node3
            ->setName('node3')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node3')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE);
        $this->dm->persist($node3);

        $node2 = new Node();
        $node2
            ->setName('node2')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node2')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node3))
            ->addNext(EmbedNode::from($node4));
        $this->dm->persist($node2);

        $node1 = new Node();
        $node1
            ->setName('node1')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node1')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node2));
        $this->dm->persist($node1);

        $this->dm->flush();
        $this->dm->clear();

        /** @var Topology $res */
        $res = $this->manager->cloneTopology($top);

        self::assertSame($top->getName(), $res->getName());
        self::assertSame($top->getVersion() + 1, $res->getVersion());
        self::assertSame($top->getDescr(), $res->getDescr());
        self::assertSame(TopologyStatusEnum::DRAFT->value, $res->getVisibility());
        self::assertSame($top->isEnabled(), $res->isEnabled());
        self::assertEquals($top->getBpmn(), $res->getBpmn());
        self::assertSame($top->getRawBpmn(), $res->getRawBpmn());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $res->getId()]);
        self::assertCount(5, $nodes);

        foreach ($nodes as $node) {
            if ($node->getName() == 'node1') {
                self::assertNodeAfterClone($node1, $node, $res, 1);
            } else if ($node->getName() == 'node2') {
                self::assertNodeAfterClone($node2, $node, $res, 2);
            } else if ($node->getName() == 'node3') {
                self::assertNodeAfterClone($node3, $node, $res, 0);
            } else if ($node->getName() == 'node4') {
                self::assertNodeAfterClone($node4, $node, $res, 1);
            } else if ($node->getName() == 'node5') {
                self::assertNodeAfterClone($node5, $node, $res, 0);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testCloneTopologyWithoutBpmn(): void
    {
        $top = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(FALSE)
            ->setDescr('desc');

        $this->dm->persist($top);
        $this->dm->flush();
        $this->dm->clear();

        /** @var Topology $res */
        $res = $this->manager->cloneTopology($top);

        self::assertSame($top->getName(), $res->getName());
        self::assertSame($top->getVersion() + 1, $res->getVersion());
        self::assertSame($top->getDescr(), $res->getDescr());
        self::assertSame(TopologyStatusEnum::DRAFT->value, $res->getVisibility());
        self::assertSame($top->isEnabled(), $res->isEnabled());
    }

    /**
     * @throws Exception
     */
    public function testSaveTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        $result = $this->manager->saveTopologySchema($topology, '', $this->getSchema());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);

        self::assertSame($topology->getId(), $result->getId());
        self::assertEquals(7, count($nodes));

        self::assertNodesFromSchemaFile($nodes);
    }

    /**
     * @throws Exception
     */
    public function testReSaveTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setContentHash('123');
        $this->pfd($topology);

        $node = (new Node())
            ->setName('deleted')
            ->setTopology($topology->getId());
        $this->pfd($node);

        $result = $this->manager->saveTopologySchema($topology, '', $this->getSchema());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);

        self::assertSame($topology->getId(), $result->getId());
        self::assertEquals(7, count($nodes));

        self::assertNodesFromSchemaFile($nodes);
    }

    /**
     * @throws Exception
     */
    public function testSaveTopologySchemaWithClone(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setContentHash('abcd');

        $this->dm->persist($topology);

        $node2 = new Node();
        $node2
            ->setName('node2')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node2')
            ->setTopology($topology->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE);
        $this->dm->persist($node2);

        $node1 = new Node();
        $node1
            ->setName('node1')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node1')
            ->setTopology($topology->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node2));
        $this->dm->persist($node1);

        $this->dm->flush();
        $this->dm->clear();

        $result = $this->manager->saveTopologySchema($topology, '', $this->getSchema());

        self::assertNotSame($topology->getId(), $result->getId());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $result->getId()]);

        self::assertNotSame($topology->getId(), $result->getId()); // because it is cloned
        self::assertEquals(7, count($nodes));

        self::assertNodesFromSchemaFile($nodes);
    }

    /**
     * @throws Exception
     */
    public function testSaveTopologySchemaUpdateNodes(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->dm->persist($topology);
        $this->dm->flush();
        $this->dm->clear();

        $result1 = $this->manager->saveTopologySchema($topology, '', $this->getSchema());
        $result2 = $this->manager->saveTopologySchema($result1, '', $this->getSchema('schema-update.json'));

        /** @var Node[] $nodes1 */
        $nodes1 = $this->dm->getRepository(Node::class)->findBy(['topology' => $result1->getId()]);

        /** @var Node[] $nodes2 */
        $nodes2 = $this->dm->getRepository(Node::class)->findBy(['topology' => $result2->getId()]);

        self::assertSame($topology->getId(), $result2->getId()); // it is only updated
        self::assertEquals(7, count($nodes2));

        self::assertSame($nodes1[0]->getId(), $nodes2[0]->getId());
        self::assertSame('Start Event', $nodes2[0]->getName());
        self::assertSame(TypeEnum::CUSTOM->value, $nodes2[0]->getType());
        self::assertSame(HandlerEnum::EVENT->value, $nodes2[0]->getHandler());

        self::assertSame($nodes1[1]->getId(), $nodes2[1]->getId());
        self::assertSame('Connector DEF', $nodes2[1]->getName());
        self::assertSame(TypeEnum::CONNECTOR->value, $nodes2[1]->getType());
        self::assertSame(HandlerEnum::ACTION->value, $nodes2[1]->getHandler());

        self::assertSame($nodes1[2]->getId(), $nodes2[2]->getId());
        self::assertSame('Mapper XYZ', $nodes2[2]->getName());
        self::assertSame(TypeEnum::MAPPER->value, $nodes2[2]->getType());
        self::assertSame(HandlerEnum::ACTION->value, $nodes2[2]->getHandler());

        self::assertSame($nodes1[3]->getId(), $nodes2[3]->getId());
        self::assertSame('Parser ABC', $nodes2[3]->getName());
        self::assertSame(TypeEnum::XML_PARSER->value, $nodes2[3]->getType());
        self::assertSame(HandlerEnum::ACTION->value, $nodes2[3]->getHandler());
        self::assertEquals(1, count($nodes2[3]->getNext()));
        self::assertSame('Connector DEF', $nodes2[3]->getNext()[0]->getName());

        self::assertSame($nodes1[4]->getId(), $nodes2[4]->getId());
        self::assertSame('Splitter SPI', $nodes2[4]->getName());
        self::assertSame(TypeEnum::SPLITTER->value, $nodes2[4]->getType());
        self::assertSame(HandlerEnum::ACTION->value, $nodes2[4]->getHandler());

        self::assertSame($nodes1[5]->getId(), $nodes2[5]->getId());
        self::assertSame('Event 1', $nodes2[5]->getName());
        self::assertSame(TypeEnum::CRON->value, $nodes2[5]->getType());
        self::assertSame(HandlerEnum::EVENT->value, $nodes2[5]->getHandler());
        self::assertEquals(1, count($nodes2[5]->getNext()));
        self::assertSame('*/2 2 * * *', $nodes2[5]->getCron());
        self::assertSame('Parser ABC', $nodes2[5]->getNext()[0]->getName());

        self::assertSame($nodes1[6]->getId(), $nodes2[6]->getId());
        self::assertSame('Event 2', $nodes2[6]->getName());
        self::assertSame(TypeEnum::WEBHOOK->value, $nodes2[6]->getType());
        self::assertSame(HandlerEnum::EVENT->value, $nodes2[6]->getHandler());
    }

    /**
     * @throws Exception
     */
    public function testSaveTopologySchemaNameNotFound(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND);

        $schema = $this->getSchema();
        unset($schema['bpmn:process']['bpmn:startEvent']['@name']);
        $this->manager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @throws Exception
     */
    public function testSaveTopologySchemaTypeNotExist(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST);

        $schema                                                        = $this->getSchema();
        $schema['bpmn:process']['bpmn:startEvent']['@pipes:pipesType'] = 'Unknown';
        $this->manager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @throws Exception
     */
    public function testSaveTopologySchemaCronNotValid(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID);

        $schema                                                     = $this->getSchema();
        $schema['bpmn:process']['bpmn:event'][0]['@pipes:cronTime'] = 'Unknown';
        $this->manager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $node  = new Node();
        $node2 = new Node();
        $top   = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::DRAFT->value);
        $this->pfd($top);
        $node->setName('node')->setType(TypeEnum::MAPPER->value)->setTopology($top->getId());
        $node2->setName('node')->setType(TypeEnum::CRON->value)->setTopology($top->getId());
        $this->pfd($node);
        $this->pfd($node2);

        $this->manager->deleteTopology($top);
        $this->dm->clear();
        self::assertEmpty(
            $this->dm->getRepository(Topology::class)->findBy(
                [
                    'deleted' => FALSE,
                    'id'      => $top->getId(),
                ],
            ),
        );
        self::assertEmpty($this->dm->getRepository(Node::class)->findBy(['id' => $node->getId(), 'deleted' => FALSE]));
        self::assertEmpty($this->dm->getRepository(Node::class)->findBy(['id' => $node2->getId(), 'deleted' => FALSE]));
    }

    /**
     * @throws Exception
     */
    public function testGetCronTopologies(): void
    {
        $tp  = (new Topology())->setName('Topology')->setVersion(1)->setEnabled(TRUE);
        $tp2 = (new Topology())->setName('Topology')->setVersion(2)->setEnabled(FALSE);
        $tp3 = (new Topology())->setName('Topology')->setVersion(2)->setEnabled(FALSE)->setDeleted(TRUE);
        $this->dm->persist($tp);
        $this->dm->persist($tp2);
        $this->dm->persist($tp3);
        $this->dm->flush();

        $cronManager = self::createMock(CronManager::class);
        $cronManager->method('getAll')->willReturn(
            new ResponseDto(
                200,
                'OK',
                sprintf(
                    '[{"topology":"%s", "node":"Node", "time":"*/1 * * * *"}, {"topology":"%s", "node":"Node", "time":"*/1 * * * *"}]',
                    $tp->getId(),
                    $tp2->getId(),
                ),
                [],
            ),
        );

        $this->setProperty($this->manager, 'cronManager', $cronManager);
        $topologies = $this->manager->getCronTopologies();

        self::assertEquals(
            [
                [
                    'node'     => [
                        'name' => 'Node',
                    ],
                    'time'     => '*/1 * * * *',
                    'topology' => [
                        'id'      => $topologies[0]['topology']['id'],
                        'name'    => 'Topology',
                        'status'  => TRUE,
                        'version' => 1,
                    ],
                ], [
                    'node'     => [
                        'name' => 'Node',
                    ],
                    'time'     => '*/1 * * * *',
                    'topology' => [
                        'id'      => $topologies[1]['topology']['id'],
                        'name'    => 'Topology',
                        'status'  => FALSE,
                        'version' => 2,
                    ],
                ],
            ],
            $topologies,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetCronTopologiesRes(): void
    {
        $tp  = (new Topology())->setName('Topology')->setVersion(1)->setEnabled(TRUE);
        $tp2 = (new Topology())->setName('Topology')->setVersion(2)->setEnabled(TRUE);
        $this->dm->persist($tp);
        $this->dm->persist($tp2);
        $this->dm->flush();

        $cronManager = self::createMock(CronManager::class);
        $cronManager->method('getAll')->willReturn(
            new ResponseDto(
                200,
                'OK',
                sprintf(
                    '[{"topology":"%s", "node":"Node", "time":"*/1 * * * *"}, {"topology":"%s", "node":"Node", "time":"*/1 * * * *"}]',
                    $tp->getId(),
                    $tp2->getId(),
                ),
                [],
            ),
        );
        $this->setProperty($this->manager, 'cronManager', $cronManager);

        $topologies = $this->manager->getCronTopologies();

        self::assertEquals(
            [
                [
                    'node'     => ['name' => 'Node'],
                    'time'     => '*/1 * * * *',
                    'topology' => [
                        'id'      => $tp2->getId(),
                        'name'    => 'Topology',
                        'status'  => TRUE,
                        'version' => 2,
                    ],
                ],
                [
                    'node'     => ['name' => 'Node'],
                    'time'     => '*/1 * * * *',
                    'topology' => [
                        'id'      => $tp->getId(),
                        'name'    => 'Topology',
                        'status'  => TRUE,
                        'version' => 1,
                    ],
                ],
            ],
            $topologies,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetCronTopologiesNotFound(): void
    {
        $cronManager = self::createMock(CronManager::class);
        $cronManager->method('getAll')->willReturn(
            new ResponseDto(200, 'OK', '[{"topology":"Topology", "node":"Node", "time":"*/1 * * * *"}]', []),
        );

        $this->setProperty($this->manager, 'cronManager', $cronManager);

        self::assertCount(0, $this->manager->getCronTopologies());
    }

    /**
     * @throws Exception
     */
    public function testSystemConfig(): void
    {
        $dto  = new SystemConfigDto('host1', 'bridge1', 2, TRUE, 2, 2);
        $node = new Node();
        $node
            ->setName('node10')
            ->setSystemConfigs($dto);

        $this->dm->persist($node);
        $this->dm->flush();
        $foundNode = $this->dm->getRepository(Node::class)->findBy(['name' => 'node10']);

        self::assertSame('node10', $foundNode[0]->getName());
        self::assertIsObject($foundNode[0]->getSystemConfigs());
    }

    /**
     * @throws Exception
     */
    public function testUnPublishTopology(): void
    {
        $topology = $this->manager->unPublishTopology(new Topology());

        self::assertSame(TopologyStatusEnum::DRAFT->value, $topology->getVisibility());
    }

    /**
     * @throws Exception
     */
    public function testUpdateNodes(): void
    {
        $node = (new Node())->setType('api');
        $this->pfd($node);

        $topology = new Topology();
        $this->pfd($topology);

        $this->invokeMethod(
            $this->manager,
            'updateNodes',
            [
                $topology,
                (new Schema())
                    ->addNode('1', new NodeSchemaDto('handler', $node->getId(), 'api', new SystemConfigDto(), 'name')),
            ],
        );

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    public function testCheckNodeAttributes(): void
    {
        $nodeSchema = new NodeSchemaDto('handler', '1', '', new SystemConfigDto(), 'name');

        self::expectException(TopologyException::class);
        $this->invokeMethod($this->manager, 'checkNodeAttributes', [$nodeSchema]);
    }

    /**
     * @throws Exception
     */
    public function testGetNodeBySchemaId(): void
    {
        $topology = new Topology();
        $this->pfd($topology);

        self::expectException(NodeException::class);
        $this->invokeMethod($this->manager, 'getNodeBySchemaId', [$topology, '1']);
    }

    /**
     * @throws Exception
     */
    public function testSetTopologyData(): void
    {
        $topology = new Topology();
        $this->pfd($topology);

        $this->invokeMethod(
            $this->manager,
            'setTopologyData',
            [
                $topology,
                [
                    'category' => 'category',
                    'desc'     => 'desc',
                    'enabled'  => TRUE,
                    'name'     => 'name',
                ],
            ],
        );

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::getContainer()->get('hbpf.configurator.manager.topology');

        $cronManager = self::getContainer()->get('hbpf.cron.manager');
        $this->setProperty($cronManager, 'curlManager', self::createMock(CurlManagerInterface::class));
        $this->setProperty($this->manager, 'cronManager', $cronManager);
    }

    /**
     * @param string $name
     *
     * @return mixed[]
     *
     * @throws Exception
     */
    private function getSchema(string $name = 'schema.json'): array
    {
        return Json::decode(File::getContent(sprintf('%s/data/%s', __DIR__, $name)));
    }

    /**
     * @param Node[] $nodes
     */
    private function assertNodesFromSchemaFile(array $nodes): void
    {
        self::assertSame('Start Event', $nodes[0]->getName());
        self::assertSame(TypeEnum::CUSTOM->value, $nodes[0]->getType());
        self::assertSame(HandlerEnum::EVENT->value, $nodes[0]->getHandler());

        self::assertSame('Connector DEF', $nodes[1]->getName());
        self::assertSame(TypeEnum::CONNECTOR->value, $nodes[1]->getType());
        self::assertSame(HandlerEnum::ACTION->value, $nodes[1]->getHandler());

        self::assertSame('Mapper XYZ', $nodes[2]->getName());
        self::assertSame(TypeEnum::MAPPER->value, $nodes[2]->getType());
        self::assertSame(HandlerEnum::ACTION->value, $nodes[2]->getHandler());

        self::assertSame('Parser ABC', $nodes[3]->getName());
        self::assertSame(TypeEnum::XML_PARSER->value, $nodes[3]->getType());
        self::assertSame(HandlerEnum::ACTION->value, $nodes[3]->getHandler());
        self::assertEquals(1, count($nodes[3]->getNext()));
        self::assertSame('Connector DEF', $nodes[3]->getNext()[0]->getName());

        self::assertSame('Splitter SPI', $nodes[4]->getName());
        self::assertSame(TypeEnum::SPLITTER->value, $nodes[4]->getType());
        self::assertSame(HandlerEnum::ACTION->value, $nodes[4]->getHandler());

        self::assertSame('Event 1', $nodes[5]->getName());
        self::assertSame(TypeEnum::CRON->value, $nodes[5]->getType());
        self::assertSame(HandlerEnum::EVENT->value, $nodes[5]->getHandler());
        self::assertEquals(1, count($nodes[5]->getNext()));
        self::assertSame('*/2 * * * *', $nodes[5]->getCron());
        self::assertSame('Parser ABC', $nodes[5]->getNext()[0]->getName());

        self::assertSame('Event 2', $nodes[6]->getName());
        self::assertSame(TypeEnum::WEBHOOK->value, $nodes[6]->getType());
        self::assertSame(HandlerEnum::EVENT->value, $nodes[6]->getHandler());
    }

    /**
     * @param Node     $expected
     * @param Node     $actual
     * @param Topology $topology
     * @param int      $nextCount
     *
     * @throws Exception
     */
    private function assertNodeAfterClone(Node $expected, Node $actual, Topology $topology, int $nextCount): void
    {
        self::assertFalse($expected->getId() == $actual->getId());
        self::assertSame($expected->getName(), $actual->getName());
        self::assertSame($expected->getType(), $actual->getType());
        self::assertSame($topology->getId(), $actual->getTopology());
        self::assertSame($expected->getHandler(), $actual->getHandler());
        self::assertSame($expected->isEnabled(), $actual->isEnabled());

        // next
        self::assertEquals($nextCount, count($expected->getNext()));
        self::assertEquals($nextCount, count($actual->getNext()));

        /** @var EmbedNode[] $expNext */
        $expNext = $expected->getNext();
        /** @var EmbedNode[] $actNext */
        $actNext = $actual->getNext();

        if ($nextCount == 1) {
            self::assertFalse($expNext[0]->getId() == $actNext[0]->getId());
            self::assertSame($expNext[0]->getName(), $actNext[0]->getName());
        } else if ($nextCount == 2) {
            self::assertFalse($expNext[0]->getId() == $actNext[0]->getId());
            self::assertSame($expNext[0]->getName(), $actNext[0]->getName());
            self::assertSame($expNext[1]->getName(), $actNext[1]->getName());
        }
    }

}
