<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Cron\CronExpression;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Cron\CronManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Exception\CronException;
use Hanaboso\PipesFramework\Commons\Exception\EnumException;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Nette\Utils\Strings;

/**
 * Class TopologyManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
class TopologyManager
{

    public const DEFAULT_SCHEME = '<?xml version="1.0" encoding="UTF-8"?><bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn"><bpmn:process id="Process_1" isExecutable="false" /><bpmndi:BPMNDiagram id="BPMNDiagram_1"><bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1" /></bpmndi:BPMNDiagram></bpmn:definitions>';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var TopologyRepository|ObjectRepository
     */
    private $topologyRepository;

    /**
     * @var CronManager
     */
    private $cronManager;

    /**
     * TopologyManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param CronManager            $cronManager
     */
    function __construct(DatabaseManagerLocator $dml, CronManager $cronManager)
    {
        $this->dm                 = $dml->getDm();
        $this->topologyRepository = $this->dm->getRepository(Topology::class);
        $this->cronManager        = $cronManager;
    }

    /**
     * @param array $data
     *
     * @return Topology
     * @throws TopologyException
     */
    public function createTopology(array $data): Topology
    {
        if ($this->topologyRepository->getTopologiesCountByName($data['name']) > 0) {
            throw new TopologyException(
                sprintf('Topology with name \'%s\' already exists', $data['name']),
                TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS
            );
        }

        $topology = $this->setTopologyData(new Topology(), $data);
        $topology->setRawBpmn(self::DEFAULT_SCHEME);

        $this->dm->persist($topology);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param array    $data
     *
     * @return Topology
     * @throws TopologyException
     */
    public function updateTopology(Topology $topology, array $data): Topology
    {
        $topology = $this->checkTopologyName($topology, $data);
        $topology = $this->setTopologyData($topology, $data);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param string   $content
     * @param array    $data
     *
     * @return Topology
     * @throws NodeException
     * @throws TopologyException
     */
    public function saveTopologySchema(Topology $topology, string $content, array $data): Topology
    {
        $newSchemaObject = TopologySchemaUtils::getSchemaObject($data);
        $newSchemaMd5    = TopologySchemaUtils::getIndexHash($newSchemaObject);

        if ($topology->getContentHash() !== $newSchemaMd5) {
            $topology->setContentHash($newSchemaMd5);

            if ($topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
                $topology = $this->cloneTopology($topology);
            }
        }

        $topology
            ->setBpmn($data)
            ->setRawBpmn($content);

        $this->generateNodes($topology, $newSchemaObject);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     * @throws TopologyException
     */
    public function publishTopology(Topology $topology): Topology
    {
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);
        if (empty($nodes)) {
            throw new TopologyException(
                'Topology has no nodes.',
                TopologyException::TOPOLOGY_HAS_NO_NODES
            );
        }

        $topology->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     * @throws NodeException
     */
    public function cloneTopology(Topology $topology): Topology
    {
        $version = $this->topologyRepository->getMaxVersion($topology->getName());
        $res     = (new Topology())
            ->setName($topology->getName())
            ->setVersion($version + 1)
            ->setDescr($topology->getDescr())
            ->setCategory($topology->getCategory())
            ->setEnabled(FALSE)
            ->setBpmn($topology->getBpmn())
            ->setRawBpmn($topology->getRawBpmn());

        $this->dm->persist($res);

        /** @var Node[] $topologyNodes */
        $topologyNodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);
        $nodesMap      = [];

        foreach ($topologyNodes as $topologyNode) {
            $nodeCopy = (new Node())
                ->setName($topologyNode->getName())
                ->setType($topologyNode->getType())
                ->setTopology($res->getId())
                ->setHandler($topologyNode->getHandler())
                ->setEnabled($topologyNode->isEnabled());
            $this->dm->persist($nodeCopy);

            $nodesMap[$topologyNode->getId()] = ['orig' => $topologyNode, 'copy' => $nodeCopy];
        }

        /** @var array $node */
        foreach ($nodesMap as $node) {

            /** @var Node $orig */
            /** @var Node $copy */
            $orig = $node['orig'];
            $copy = $node['copy'];

            if (!$orig->getNext()->isEmpty()) {
                $nexts = $orig->getNext();
                foreach ($nexts as $next) {
                    $copy->addNext(EmbedNode::from($nodesMap[$next->getId()]['copy']));
                }
            }
        }

        $this->dm->flush();

        return $res;
    }

    /**
     * @param Topology $topology
     *
     * @throws TopologyException
     */
    public function deleteTopology(Topology $topology): void
    {
        if ($topology->getVisibility() === TopologyStatusEnum::PUBLIC && $topology->isEnabled()) {
            throw new TopologyException(
                'Cannot delete published topology which is enabled. Disable it first.',
                TopologyException::CANNOT_DELETE_PUBLIC_TOPOLOGY
            );
        }

        $this->removeNodesByTopology($topology);
        $topology->setDeleted(TRUE);
        $this->dm->flush();
    }

    /**
     * @param Topology $topology
     */
    private function removeNodesByTopology(Topology $topology): void
    {
        /** @var Node $node */
        foreach ($this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]) as $node) {
            $node->setDeleted(TRUE);
        }

        $this->dm->flush();
    }

    /**
     * @param Topology $topology
     * @param Schema   $dto
     *
     * @throws TopologyException
     */
    private function generateNodes(Topology $topology, Schema $dto): void
    {
        $this->removeNodesByTopology($topology);

        /** @var Node[] $nodes */
        /** @var EmbedNode[] $embedNodes */
        $nodes      = [];
        $embedNodes = [];

        foreach ($dto->getNodes() as $id => $node) {
            $this->createNode(
                $topology,
                $id,
                $node['handler'],
                $node['name'],
                $node['pipes_type'],
                $node['cron_time'],
                $nodes,
                $embedNodes
            );
        }

        foreach ($dto->getSequences() as $source => $targets) {
            foreach ($targets as $target) {
                if (isset($nodes[$source]) && $embedNodes[$target]) {
                    $nodes[$source]->addNext($embedNodes[$target]);
                }
            }
        }
    }

    /**
     * @param Topology    $topology
     * @param string      $id
     * @param string      $handler
     * @param string|null $name
     * @param string|null $type
     * @param string|null $cron
     * @param array       $nodes
     * @param array       $embedNodes
     *
     * @return Node
     * @throws TopologyException
     */
    private function createNode(
        Topology $topology,
        string $id,
        string $handler,
        ?string $name = NULL,
        ?string $type = NULL,
        ?string $cron = NULL,
        array &$nodes,
        array &$embedNodes
    ): Node
    {
        if (!$name) {
            throw new TopologyException(
                sprintf('Node [%s] name not found', $id),
                TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND
            );
        }

        if (!$type) {
            throw new TopologyException(
                sprintf('Node [%s] type not found', $id),
                TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND
            );
        }

        try {
            $type = (new TypeEnum($type))->getValue();
        } catch (EnumException $e) {
            throw new TopologyException(
                sprintf('Node [%s] type [%s] not exist', $id, $type),
                TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST
            );
        }

        if ($cron && !CronExpression::isValidExpression($cron)) {
            throw new TopologyException(
                sprintf('Node [%s] cron [%s] not valid', $id, $type),
                TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID
            );
        }

        $node = (new Node())
            ->setName($name)
            ->setType($type)
            ->setTopology($topology->getId())
            ->setHandler(Strings::endsWith($handler, 'vent') ? HandlerEnum::EVENT : HandlerEnum::ACTION)
            ->setCron($cron);
        $this->dm->persist($node);

        $nodes[$id]      = $node;
        $embedNodes[$id] = EmbedNode::from($node);

        if ($cron) {
            try {
                $this->cronManager->patch($node);
            } catch (CronException $e) {
                throw new TopologyException(
                    sprintf('Saving of Node [%s] & cron [%s] failed.', $id, $type),
                    TopologyException::TOPOLOGY_NODE_CRON_NOT_AVAILABLE,
                    $e
                );
            }
        }

        return $node;
    }

    /**
     * @param Topology $topology
     * @param array    $data
     *
     * @return Topology
     */
    private function setTopologyData(Topology $topology, array $data): Topology
    {
        if (isset($data['name'])) {
            $topology->setName($data['name']);
        }

        if (isset($data['descr'])) {
            $topology->setDescr($data['descr']);
        }

        if (isset($data['enabled'])) {
            $topology->setEnabled($data['enabled']);
        }

        if (array_key_exists('category', $data)) {
            $topology->setCategory($data['category']);
        }

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param array    $data
     *
     * @return Topology
     * @throws TopologyException
     */
    private function checkTopologyName(Topology $topology, array $data): Topology
    {
        if (isset($data['name']) && $topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
            throw new TopologyException(
                'Cannot change name of published topology',
                TopologyException::TOPOLOGY_CANNOT_CHANGE_NAME
            );
        }

        if (isset($data['name']) && $topology->getName() !== $data['name']) {
            if ($this->topologyRepository->getTopologiesCountByName($data['name']) > 0) {
                throw new TopologyException(
                    sprintf('Topology with name \'%s\' already exists', $data['name']),
                    TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS
                );
            }
        }

        return $topology;
    }

}
