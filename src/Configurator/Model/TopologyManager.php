<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use Hanaboso\Utils\Cron\CronParser;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\String\Strings;
use Hanaboso\Utils\Traits\UrlBuilderTrait;
use JsonException;

/**
 * Class TopologyManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class TopologyManager
{

    use UrlBuilderTrait;

    public const DEFAULT_SCHEME = '<?xml version="1.0" encoding="UTF-8"?><bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn"><bpmn:process id="%s" isExecutable="false" /><bpmndi:BPMNDiagram id="BPMNDiagram_1"><bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1" /></bpmndi:BPMNDiagram></bpmn:definitions>';

    private const RUN_ENDPOINT = 'topologies/%s/nodes/%s/run';

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var ObjectRepository<Topology>&TopologyRepository
     */
    private TopologyRepository $topologyRepository;

    /**
     * @var NodeRepository
     */
    private NodeRepository $nodeRepository;

    /**
     * TopologyManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param CronManager            $cronManager
     * @param bool                   $checkInfiniteLoop
     * @param CurlManagerInterface   $curl
     * @param string                 $apiKey
     * @param string                 $startingPointHost
     */
    function __construct(
        DatabaseManagerLocator $dml,
        private CronManager $cronManager,
        private bool $checkInfiniteLoop,
        private CurlManagerInterface $curl,
        private readonly string $apiKey,
        string $startingPointHost,
    )
    {
        /** @var DocumentManager $dm */
        $dm       = $dml->getDm();
        $this->dm = $dm;

        /** @var TopologyRepository $topoRepo */
        $topoRepo                 = $this->dm->getRepository(Topology::class);
        $this->topologyRepository = $topoRepo;

        /** @var NodeRepository $nodeRepo */
        $nodeRepo             = $this->dm->getRepository(Node::class);
        $this->nodeRepository = $nodeRepo;

        $this->host = rtrim($startingPointHost, '/');
    }

    /**
     * @param string  $topologyId
     * @param mixed[] $startingPoints
     * @param string  $data
     *
     * @return mixed[]
     * @throws CurlException
     */
    public function runTopology(string $topologyId, array $startingPoints, string $data): array
    {
        $successfull = 0;
        $errors      = [];

        foreach ($startingPoints as $startingPointId) {
            $request = new RequestDto(
                new Uri($this->getUrl(self::RUN_ENDPOINT, $topologyId, $startingPointId)),
                CurlManager::METHOD_POST,
                new ProcessDto(),
                '',
                [
                    'orchesty-api-key' => $this->apiKey,
                ],
            );
            $request->setBody($data);

            try {
                $this->curl->send($request);
                $successfull++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return [$successfull, $errors];
    }

    /**
     * @param mixed[] $data
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
                TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS,
            );
        }

        $topology = $this->setTopologyData(new Topology(), $data);
        $topology->setVersion($this->topologyRepository->getMaxVersion($data['name']) + 1);
        $topology->setRawBpmn(sprintf(self::DEFAULT_SCHEME, $topology->getName()));

        $this->dm->persist($topology);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param mixed[]  $data
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
     * @param mixed[]  $data
     *
     * @return Topology
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     * @throws TopologyException
     * @throws MongoDBException
     * @throws JsonException
     */
    public function saveTopologySchema(Topology $topology, string $content, array $data): Topology
    {
        $newSchemaObject = TopologySchemaUtils::getSchemaObject($data);
        $newSchemaSha256 = TopologySchemaUtils::getIndexHash($newSchemaObject, $this->checkInfiniteLoop);

        $cloned              = FALSE;
        $originalContentHash = $topology->getContentHash();

        if ($originalContentHash !== $newSchemaSha256) {
            if (!empty($originalContentHash) && $topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
                $topology = $this->cloneTopologyShallow($topology, $newSchemaSha256);
                $cloned   = TRUE;
            } else {
                $topology->setContentHash($newSchemaSha256);
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
            ->setApplications($newSchemaObject->getApplicationList())
            ->setBpmn($data)
            ->setRawBpmn($content);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param mixed[]  $data
     *
     * @return bool
     * @throws TopologyException
     */
    public function checkTopologySchemaIsSame(Topology $topology, array $data): bool
    {
        $oldSchemaObject = TopologySchemaUtils::getSchemaObject($topology->getBpmn());
        $newSchemaObject = TopologySchemaUtils::getSchemaObject($data);

        $oldSchemaSha256 = TopologySchemaUtils::getSchemaFullIndexHash($oldSchemaObject);
        $newSchemaSha256 = TopologySchemaUtils::getSchemaFullIndexHash($newSchemaObject);

        return $oldSchemaSha256 === $newSchemaSha256;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     * @throws TopologyException
     * @throws EnumException
     * @throws MongoDBException
     */
    public function publishTopology(Topology $topology): Topology
    {
        $nodes = $this->nodeRepository->findBy(['topology' => $topology->getId()]);
        if (empty($nodes)) {
            throw new TopologyException(
                'Topology has no nodes. Please save your topology before publish it.',
                TopologyException::TOPOLOGY_HAS_NO_NODES,
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
     * @throws MongoDBException
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
     * @throws TopologyException
     * @throws MongoDBException
     * @throws JsonException
     */
    public function cloneTopology(Topology $topology): Topology
    {
        $res = $this->cloneTopologyShallow($topology, $topology->getContentHash());

        /** @var Node[] $topologyNodes */
        $topologyNodes = $this->nodeRepository->findBy(['topology' => $topology->getId()]);
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

            $settings = $topologyNode->getSystemConfigs();
            if ($settings) {
                $nodeCopy->setSystemConfigs($settings);
            }

            $this->dm->flush();

            $this->makePatchRequestForCron($nodeCopy, $nodeCopy->getType(), $res->getId());

            $nodesMap[$topologyNode->getId()] = ['orig' => $topologyNode, 'copy' => $nodeCopy];
        }

        /** @var mixed[] $node */
        foreach ($nodesMap as $node) {
            /** @var Node $orig */
            $orig = $node['orig'];
            /** @var Node $copy */
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
     * @throws CronException
     * @throws CurlException
     * @throws TopologyException
     * @throws MongoDBException
     */
    public function deleteTopology(Topology $topology): void
    {
        $this->removeNodesByTopology($topology);
        $topology->setDeleted(TRUE);
        $this->dm->flush();
    }

    /**
     * @return mixed[]
     * @throws CronException
     * @throws CurlException
     * @throws JsonException
     */
    public function getCronTopologies(): array
    {
        $data   = Json::decode($this->cronManager->getAll()->getBody());
        $result = [];

        foreach ($data as $item) {
            /** @var Topology[] $topologies */
            $topologies = $this->topologyRepository->findBy(['id' => $item['topology'], 'deleted' => FALSE]);

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

        usort(
            $result,
            static function (array $one, array $two): int {
                $result = $one['topology']['status'] <=> $two['topology']['status'];

                if (!$result) {
                    $result = $one['topology']['version'] <=> $two['topology']['version'];
                }

                return $result * -1;
            },
        );

        return $result;
    }

    /**
     * @param string $topologyId
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getTopologiesById(string $topologyId): array
    {
        return array_map(
            static fn($value): array => [
                'id'      => (string) $value['_id'],
                'name'    => $value['name'],
                'version' => $value['version'],
            ],
            $this->topologyRepository->getActiveTopologiesVersions($topologyId),
        );
    }

    /**
     * ----------------------------------------------- HELPERS -----------------------------------------------
     */

    /**
     * @param Topology $topology
     * @param string   $hash
     *
     * @return Topology
     */
    private function cloneTopologyShallow(Topology $topology, string $hash): Topology
    {
        $version = $this->topologyRepository->getMaxVersion($topology->getName());
        $res     = (new Topology())
            ->setName($topology->getName())
            ->setVersion($version + 1)
            ->setDescr($topology->getDescr())
            ->setCategory($topology->getCategory())
            ->setEnabled(FALSE)
            ->setContentHash($hash)
            ->setBpmn($topology->getBpmn())
            ->setRawBpmn($topology->getRawBpmn());

        $this->dm->persist($res);

        return $res;
    }

    /**
     * @param Topology $topology
     *
     * @throws CronException
     * @throws CurlException
     * @throws MongoDBException
     */
    private function removeNodesByTopology(Topology $topology): void
    {
        /** @var Node $node */
        foreach ($this->nodeRepository->findBy(['topology' => $topology->getId()]) as $node) {
            $node->setDeleted(TRUE);
            if ($node->getType() === TypeEnum::CRON) {
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
     * @throws MongoDBException
     */
    private function generateNodes(Topology $topology, Schema $dto): void
    {
        /** @var Node[] $nodes */
        $nodes = [];

        foreach ($dto->getNodes() as $nodeSchemaDto) {
            $this->createNode($topology, $nodes, $nodeSchemaDto);
        }

        foreach ($dto->getSequences() as $source => $targets) {
            foreach ($targets as $target) {
                if (isset($nodes[$source]) && isset($nodes[$target])) {
                    $nodes[$source]->addNext(EmbedNode::from($nodes[$target]));
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
     * @throws MongoDBException
     */
    private function updateNodes(Topology $topology, Schema $dto): void
    {
        /** @var Node[] $nodes */
        $nodes = [];

        foreach ($dto->getNodes() as $nodeSchemaDto) {
            try {
                $this->updateNode($topology, $nodes, $nodeSchemaDto);
            } catch (NodeException $e) {
                if ($e->getCode() === NodeException::NODE_NOT_FOUND) {
                    $this->createNode($topology, $nodes, $nodeSchemaDto);
                }
            }
        }

        foreach ($dto->getSequences() as $source => $targets) {
            $nodes[$source]->setNext([]);
            foreach ($targets as $target) {
                if (isset($nodes[$source]) && $nodes[$target]) {
                    $nodes[$source]->addNext(EmbedNode::from($nodes[$target]));
                }
            }
        }

        $nodeIds = [];
        foreach ($nodes as $node) {
            $nodeIds[] = $node->getId();
        }
        $this->nodeRepository->createQueryBuilder()->remove()
            ->field('topology')->equals($topology->getId())
            ->field('_id')->notIn($nodeIds)
            ->getQuery()->execute();
    }

    /**
     * @param Topology      $topology
     * @param mixed[]       $nodes
     * @param NodeSchemaDto $dto
     *
     * @throws NodeException
     * @throws TopologyException
     * @throws MongoDBException
     */
    private function createNode(Topology $topology, array &$nodes, NodeSchemaDto $dto): void
    {
        $this->checkNodeAttributes($dto);
        $node = $this->setNodeAttributes($topology, new Node(), $dto);

        $this->dm->persist($node);
        $this->dm->flush();

        $nodes[$dto->getId()] = $node;

        $this->makePatchRequestForCron($node, $dto->getPipesType(), $dto->getId());
    }

    /**
     * @param Topology      $topology
     * @param mixed[]       $nodes
     * @param NodeSchemaDto $dto
     *
     * @throws NodeException
     * @throws TopologyException
     */
    private function updateNode(Topology $topology, array &$nodes, NodeSchemaDto $dto): void
    {
        $this->checkNodeAttributes($dto);
        $node = $this->getNodeBySchemaId($topology, $dto->getId());
        $node = $this->setNodeAttributes($topology, $node, $dto);

        $nodes[$dto->getId()] = $node;

        $this->makePatchRequestForCron($node, $dto->getPipesType(), $dto->getId());
    }

    /**
     * @param Topology      $topology
     * @param Node          $node
     * @param NodeSchemaDto $dto
     *
     * @return Node
     * @throws NodeException
     */
    private function setNodeAttributes(Topology $topology, Node $node, NodeSchemaDto $dto): Node
    {
        $node
            ->setName($dto->getName())
            ->setType($dto->getPipesType())
            ->setSchemaId($dto->getId())
            ->setSystemConfigs($dto->getSystemConfigs())
            ->setTopology($topology->getId())
            ->setHandler(Strings::endsWith($dto->getHandler(), 'vent') ? HandlerEnum::EVENT : HandlerEnum::ACTION)
            ->setCronParams(urldecode($dto->getCronParams()))
            ->setCron($dto->getCronTime())
            ->setApplication($dto->getApplication());

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
                TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND,
            );
        }

        if (!$dto->getPipesType()) {
            throw new TopologyException(
                sprintf('Node [%s] type not found', $dto->getId()),
                TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND,
            );
        }

        try {
            TypeEnum::isValid($dto->getPipesType());
        } catch (EnumException $e) {
            $e;

            throw new TopologyException(
                sprintf('Node [%s] type [%s] not exist', $dto->getId(), $dto->getPipesType()),
                TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST,
            );
        }

        if ($dto->getCronTime() && !CronParser::isValidExpression($dto->getCronTime())) {
            throw new TopologyException(
                sprintf('Node [%s] cron [%s] not valid', $dto->getId(), $dto->getPipesType()),
                TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID,
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
        $node = $this->nodeRepository->findOneBy(
            [
                'topology' => $topology->getId(),
                'schemaId' => $schemaId,
                'deleted'  => FALSE,
            ],
        );

        if (!$node) {
            throw new NodeException(
                sprintf('Node [schema id: %s] for topology %s not found.', $schemaId, $topology->getId()),
                NodeException::NODE_NOT_FOUND,
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
        if ($type === TypeEnum::CRON) {
            try {
                $this->cronManager->patch($node, empty($node->getCron()));
            } catch (CronException|CurlException $e) {
                throw new TopologyException(
                    sprintf('Saving of Node [%s] & cron [%s] failed.', $schemaId, $type),
                    TopologyException::TOPOLOGY_NODE_CRON_NOT_AVAILABLE,
                    $e,
                );
            }
        }
    }

    /**
     * @param Topology $topology
     * @param mixed[]  $data
     *
     * @return Topology
     */
    private function setTopologyData(Topology $topology, array $data): Topology
    {
        if (isset($data['name'])) {
            $topology->setName($data['name']);
        }

        if (isset($data['description'])) {
            $topology->setDescr($data['description']);
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
     * @param mixed[]  $data
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
                TopologyException::TOPOLOGY_CANNOT_CHANGE_NAME,
            );
        }

        if (isset($data['name']) && $topology->getName() !== $data['name']) {
            if ($this->topologyRepository->getTopologiesCountByName($data['name']) > 0) {
                throw new TopologyException(
                    sprintf('Topology with name \'%s\' already exists', $data['name']),
                    TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS,
                );
            }
        }

        return $topology;
    }

    /**
     * @param mixed[] $data
     */
    private function normalizeName(array &$data): void
    {
        if (isset($data['name'])) {
            $data['name'] = Strings::webalize($data['name']);
        }
    }

}
