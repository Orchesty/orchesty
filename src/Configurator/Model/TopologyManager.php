<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Cron\CronExpression;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto;
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
        /** @var DocumentManager $dm */
        $dm                       = $dml->getDm();
        $this->dm                 = $dm;
        $this->topologyRepository = $this->dm->getRepository(Topology::class);
        $this->cronManager        = $cronManager;
    }

    /**
     * @param array $data
     *
     * @return Topology
     * @throws TopologyException
     * @throws MongoDBException
     */
    public function createTopology(array $data): Topology
    {
        $this->normalizeName($data);
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
     * @throws MongoDBException
     */
    public function updateTopology(Topology $topology, array $data): Topology
    {
        $this->normalizeName($data);
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
     * @throws EnumException
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
     * @throws EnumException
     */
    public function publishTopology(Topology $topology): Topology
    {
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);
        if (empty($nodes)) {
            throw new TopologyException(
                'Topology has no nodes. Please save your topology before publish it.',
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
     * @throws EnumException
     */
    public function unPublishTopology(Topology $topology): Topology
    {
        $topology->setVisibility(TopologyStatusEnum::DRAFT);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     * @throws NodeException
     * @throws EnumException
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

            if (!empty($orig->getNext())) {
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
     * @return array
     * @throws CronException
     * @throws CurlException
     */
    public function getCronTopologies(): array
    {
        $data   = json_decode($this->cronManager->getAll()->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
        $result = [];

        foreach ($data as $item) {
            /** @var Topology[] $topologies */
            $topologies = $this->topologyRepository->findBy(['name' => $item['topology'],'deleted' => FALSE]);

            foreach ($topologies as $topology) {
                $result[] = [
                    'topology' => [
                        'id'      => $topology->getId(),
                        'name'    => $topology->getName(),
                        'status'  => $topology->isEnabled(),
                        'version' => $topology->getVersion(),
                    ],
                    'node'     => [
                        'name' => $item['node'],
                    ],
                    'time'     => $item['time'],
                ];
            }
        }

        usort($result, function (array $one, array $two): int {
            $result = $one['topology']['status'] <=> $two['topology']['status'];

            if (!$result) {
                $result = $one['topology']['version'] <=> $two['topology']['version'];
            }

            return $result * -1;
        });

        return $result;
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
            if($node->getType() === TypeEnum::CRON){
                $this->cronManager->delete($node);
            }
        }

        $this->dm->flush();
    }

    /**
     * @param Topology $topology
     * @param Schema   $dto
     *
     * @throws TopologyException
     * @throws NodeException
     * @throws EnumException
     */
    private function generateNodes(Topology $topology, Schema $dto): void
    {
        /** @var Node[] $nodes */
        /** @var EmbedNode[] $embedNodes */
        $nodes      = [];
        $embedNodes = [];

        foreach ($dto->getNodes() as $nodeSchemaDto) {
            $this->createNode($topology, $nodes, $embedNodes, $nodeSchemaDto);
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
     * @throws EnumException
     */
    private function updateNodes(Topology $topology, Schema $dto): void
    {
        /** @var Node[] $nodes */
        /** @var EmbedNode[] $embedNodes */
        $nodes      = [];
        $embedNodes = [];

        foreach ($dto->getNodes() as $nodeSchemaDto) {
            try {
                $this->updateNode($topology, $nodes, $embedNodes, $nodeSchemaDto);
            } catch (NodeException $e) {
                if ($e->getCode() === NodeException::NODE_NOT_FOUND) {
                    $this->createNode($topology, $nodes, $embedNodes, $nodeSchemaDto);
                }
            }
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
     * @param Topology      $topology
     * @param array         $nodes
     * @param array         $embedNodes
     * @param NodeSchemaDto $dto
     *
     * @return Node
     * @throws EnumException
     * @throws NodeException
     * @throws TopologyException
     */
    private function createNode(Topology $topology, array &$nodes, array &$embedNodes, NodeSchemaDto $dto): Node
    {
        $this->checkNodeAttributes($dto);
        $node = $this->setNodeAttributes($topology, new Node(), $dto);

        $this->dm->persist($node);
        $this->dm->flush();

        $nodes[$dto->getId()]      = $node;
        $embedNodes[$dto->getId()] = EmbedNode::from($node);

        $this->makePatchRequestForCron($node, (string) $dto->getPipesType(), $dto->getId());

        return $node;
    }

    /**
     * @param Topology      $topology
     * @param array         $nodes
     * @param array         $embedNodes
     * @param NodeSchemaDto $dto
     *
     * @return Node
     * @throws EnumException
     * @throws NodeException
     * @throws TopologyException
     */
    private function updateNode(
        Topology $topology,
        array &$nodes,
        array &$embedNodes,
        NodeSchemaDto $dto
    ): Node
    {
        $this->checkNodeAttributes($dto);
        $node = $this->getNodeBySchemaId($topology, $dto->getId());
        $node = $this->setNodeAttributes($topology, $node, $dto);

        $nodes[$dto->getId()]      = $node;
        $embedNodes[$dto->getId()] = EmbedNode::from($node);

        $this->makePatchRequestForCron($node, $dto->getPipesType(), $dto->getId());

        return $node;
    }

    /**
     * @param Topology      $topology
     * @param Node          $node
     * @param NodeSchemaDto $dto
     *
     * @return Node
     * @throws EnumException
     * @throws NodeException
     */
    private function setNodeAttributes(Topology $topology, Node $node, NodeSchemaDto $dto): Node
    {
        $node
            ->setName($dto->getName())
            ->setType($dto->getPipesType())
            ->setSchemaId($dto->getId())
            ->setTopology($topology->getId())
            ->setHandler(Strings::endsWith($dto->getHandler(), 'vent') ? HandlerEnum::EVENT : HandlerEnum::ACTION)
            ->setCronParams(urldecode((string) $dto->getCronParams()))
            ->setCron($dto->getCronTime());

        return $node;
    }

    /**
     * @param NodeSchemaDto $dto
     *
     * @throws TopologyException
     */
    private function checkNodeAttributes(NodeSchemaDto $dto): void
    {
        if (!$dto->getName()) {
            throw new TopologyException(
                sprintf('Node [%s] name not found', $dto->getId()),
                TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND
            );
        }

        if (!$dto->getPipesType()) {
            throw new TopologyException(
                sprintf('Node [%s] type not found', $dto->getId()),
                TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND
            );
        }

        try {
            TypeEnum::isValid($dto->getPipesType());
        } catch (EnumException $e) {
            throw new TopologyException(
                sprintf('Node [%s] type [%s] not exist', $dto->getId(), $dto->getPipesType()),
                TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST
            );
        }

        if ($dto->getCronTime() && !CronExpression::isValidExpression($dto->getCronTime())) {
            throw new TopologyException(
                sprintf('Node [%s] cron [%s] not valid', $dto->getId(), $dto->getPipesType()),
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
        /** @var Node|null $node */
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
                $this->cronManager->patch($node, empty($node->getCron()));
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
     * @throws MongoDBException
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

    /**
     * @param array $data
     */
    private function normalizeName(array &$data): void
    {
        if (isset($data['name'])) {
            $data['name'] = Strings::webalize($data['name']);
        }
    }

}
