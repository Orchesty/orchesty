<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Cron\CronExpression;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Exception\CronException;
use Hanaboso\PipesFramework\Commons\Exception\EnumException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
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

        $cloned              = FALSE;
        $originalContentHash = $topology->getContentHash();

        if ($originalContentHash !== $newSchemaMd5) {
            $topology->setContentHash($newSchemaMd5);

            if (!empty($originalContentHash) && $topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
                $topology = $this->cloneTopologyShallow($topology);
                $cloned   = TRUE;
            }
        }

        try {
            if ($cloned || empty($originalContentHash)) {
                $this->generateNodes($topology, $newSchemaObject); // first save of topology or after topology is cloned
            } else {
                $this->updateNodes($topology, $newSchemaObject);
            }
        } catch (TopologyException $e) {
            $topology->setContentHash('');
            $this->removeNodesByTopology($topology);
            throw $e;
        }

        $topology
            ->setBpmn($data)
            ->setRawBpmn($content);
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
     * @throws TopologyException
     */
    public function unPublishTopology(Topology $topology): Topology
    {
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);
        if (empty($nodes)) {
            throw new TopologyException(
                'Topology has no nodes.',
                TopologyException::TOPOLOGY_HAS_NO_NODES
            );
        }

        $topology->setVisibility(TopologyStatusEnum::DRAFT);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     */
    public function cloneTopology(Topology $topology): Topology
    {
        $res = $this->cloneTopologyShallow($topology);

        /** @var Node[] $topologyNodes */
        $topologyNodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);
        $nodesMap      = [];

        foreach ($topologyNodes as $topologyNode) {
            $nodeCopy = (new Node())
                ->setSchemaId($topologyNode->getSchemaId())
                ->setName($topologyNode->getName())
                ->setType($topologyNode->getType())
                ->setTopology($res->getId())
                ->setHandler($topologyNode->getHandler())
                ->setEnabled($topologyNode->isEnabled())
                ->setCron($topologyNode->getCron())
                ->setCronParams($topologyNode->getCronParams());
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
     * ----------------------------------------------- HELPERS -----------------------------------------------
     */

    /**
     * @param Topology $topology
     *
     * @return Topology
     */
    private function cloneTopologyShallow(Topology $topology): Topology
    {
        $version = $this->topologyRepository->getMaxVersion($topology->getName());
        $res     = (new Topology())
            ->setName($topology->getName())
            ->setVersion($version + 1)
            ->setDescr($topology->getDescr())
            ->setCategory($topology->getCategory())
            ->setEnabled(FALSE)
            ->setContentHash($topology->getContentHash())
            ->setBpmn($topology->getBpmn())
            ->setRawBpmn($topology->getRawBpmn());

        $this->dm->persist($res);

        return $res;
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
        /** @var Node[] $nodes */
        /** @var EmbedNode[] $embedNodes */
        $nodes      = [];
        $embedNodes = [];

        foreach ($dto->getNodes() as $id => $node) {
            $this->createNode(
                $topology,
                $id,
                $node['handler'],
                $nodes,
                $embedNodes,
                $node['name'],
                $node['pipes_type'],
                $node['cron_time'],
                $node['cron_params']
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
     * @param Topology $topology
     * @param Schema   $dto
     *
     * @throws NodeException
     * @throws TopologyException
     */
    private function updateNodes(Topology $topology, Schema $dto): void
    {
        /** @var Node[] $nodes */
        /** @var EmbedNode[] $embedNodes */
        $nodes      = [];
        $embedNodes = [];

        foreach ($dto->getNodes() as $id => $node) {
            $this->updateNode(
                $topology,
                $id,
                $node['handler'],
                $nodes,
                $embedNodes,
                $node['name'],
                $node['pipes_type'],
                $node['cron_time'],
                $node['cron_params']
            );
        }

        foreach ($dto->getSequences() as $source => $targets) {
            $nodes[$source]->setNext([]);
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
     * @param array       $nodes
     * @param array       $embedNodes
     * @param string|null $name
     * @param string|null $type
     * @param string|null $cron
     * @param string|null $cron_params
     *
     * @return Node
     * @throws TopologyException
     */
    private function createNode(
        Topology $topology,
        string $id,
        string $handler,
        array &$nodes,
        array &$embedNodes,
        ?string $name = NULL,
        ?string $type = NULL,
        ?string $cron = NULL,
        ?string $cron_params = NULL
    ): Node
    {
        $this->checkNodeAttributes($id, $name, $type, $cron);

        $node = $this->setNodeAttributes($topology, new Node(), $name, $type, $id, $handler, $cron, $cron_params);

        $this->dm->persist($node);
        $this->dm->flush();

        $nodes[$id]      = $node;
        $embedNodes[$id] = EmbedNode::from($node);

        $this->makePatchRequestForCron($node, $type, $id);

        return $node;
    }

    /**
     * @param Topology    $topology
     * @param string      $id
     * @param string      $handler
     * @param array       $nodes
     * @param array       $embedNodes
     * @param string|null $name
     * @param string|null $type
     * @param string|null $cron
     * @param string|null $cron_params
     *
     * @return Node
     * @throws NodeException
     * @throws TopologyException
     */
    private function updateNode(
        Topology $topology,
        string $id,
        string $handler,
        array &$nodes,
        array &$embedNodes,
        ?string $name = NULL,
        ?string $type = NULL,
        ?string $cron = NULL,
        ?string $cron_params = NULL
    ): Node
    {
        $this->checkNodeAttributes($id, $name, $type, $cron);

        $node = $this->getNodeBySchemaId($topology, $id);
        $node = $this->setNodeAttributes($topology, $node, $name, $type, $id, $handler, $cron, $cron_params);

        $nodes[$id]      = $node;
        $embedNodes[$id] = EmbedNode::from($node);

        $this->makePatchRequestForCron($node, $type, $id);

        return $node;
    }

    /**
     * @param Topology    $topology
     * @param Node        $node
     * @param string      $name
     * @param string      $type
     * @param string      $schemaId
     * @param string      $handler
     * @param null|string $cron
     * @param null|string $cron_params
     *
     * @return Node
     */
    private function setNodeAttributes(
        Topology $topology,
        Node $node,
        string $name,
        string $type,
        string $schemaId,
        string $handler,
        ?string $cron = NULL,
        ?string $cron_params = NULL
    ): Node
    {
        $node
            ->setName($name)
            ->setType($type)
            ->setSchemaId($schemaId)
            ->setTopology($topology->getId())
            ->setHandler(Strings::endsWith($handler, 'vent') ? HandlerEnum::EVENT : HandlerEnum::ACTION)
            ->setCronParams(urldecode($cron_params))
            ->setCron($cron);

        return $node;
    }

    /**
     * @param string      $id
     * @param null|string $name
     * @param null|string $type
     * @param null|string $cron
     *
     * @throws TopologyException
     */
    private function checkNodeAttributes(
        string $id,
        ?string $name = NULL,
        ?string $type = NULL,
        ?string $cron = NULL
    ): void
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
    }

    /**
     * @param Topology $topology
     * @param string   $schemaId
     *
     * @return Node
     * @throws NodeException
     */
    private function getNodeBySchemaId(Topology $topology, string $schemaId): Node
    {
        /** @var Node $node */
        $node = $this->dm->getRepository(Node::class)->findOneBy([
            'topology' => $topology->getId(),
            'schemaId' => $schemaId,
            'deleted'  => FALSE,
        ]);

        if (!$node) {
            throw new NodeException(
                sprintf('Node [schema id: %s] for topology %s not found.', $schemaId, $topology->getId()),
                NodeException::NODE_NOT_FOUND
            );
        }

        return $node;
    }

    /**
     * @param Node   $node
     * @param string $type
     * @param string $schemaId
     *
     * @throws TopologyException
     */
    private function makePatchRequestForCron(Node $node, string $type, string $schemaId): void
    {
        if ($type == TypeEnum::CRON) {
            try {
                $this->cronManager->patch($node, empty($cron));
            } catch (CronException | CurlException $e) {
                throw new TopologyException(
                    sprintf('Saving of Node [%s] & cron [%s] failed.', $schemaId, $type),
                    TopologyException::TOPOLOGY_NODE_CRON_NOT_AVAILABLE,
                    $e
                );
            }
        }
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
