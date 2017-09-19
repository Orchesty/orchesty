<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Exception\EnumException;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;

/**
 * Class TopologyManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
class TopologyManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * TopologyManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     */
    function __construct(DatabaseManagerLocator $dml)
    {
        $this->dm = $dml->getDm();
    }

    /**
     * @param array $data
     *
     * @return Topology
     */
    public function createTopology(array $data): Topology
    {
        $topology = $this->setTopologyData(new Topology(), $data);

        $this->dm->persist($topology);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param array    $data
     *
     * @return Topology
     */
    public function updateTopology(Topology $topology, array $data): Topology
    {
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
     */
    public function saveTopologySchema(Topology $topology, string $content, array $data): Topology
    {
        if ($topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
            $topology = $this->cloneTopology($topology);
        }

        $topology
            ->setBpmn($data)
            ->setRawBpmn($content);

        $this->generateNodes($topology, $data);

        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     */
    public function publishTopology(Topology $topology): Topology
    {
        $topology->setVisibility(TopologyStatusEnum::PUBLIC);
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
        $res = new Topology();
        $res
            ->setName($topology->getName() . ' - copy')
            ->setDescr($topology->getDescr())
            ->setEnabled($topology->isEnabled())
            ->setBpmn($topology->getBpmn())
            ->setRawBpmn($topology->getRawBpmn());

        $this->dm->persist($res);
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
        if ($topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
            throw new TopologyException(
                'Cannot delete published topology.',
                TopologyException::CANNOT_DELETE_PUBLIC_TOPOLOGY
            );
        }

        $this->removeNodesByTopology($topology);
        $this->dm->remove($topology);
        $this->dm->flush();
    }

    /**
     * @param Topology $topology
     */
    private function removeNodesByTopology(Topology $topology): void
    {
        foreach ($this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]) as $node) {
            $this->dm->remove($node);
        }

        $this->dm->flush();
    }

    /**
     * @param Topology $topology
     * @param array    $data
     */
    private function generateNodes(Topology $topology, array $data): void
    {
        $this->removeNodesByTopology($topology);

        if (isset($data['bpmn:process'])) {
            /** @var Node[] $nodes */
            $nodes = [];
            /** @var EmbedNode[] $embedNodes */
            $embedNodes = [];

            foreach ($data['bpmn:process'] as $handler => $process) {
                if (in_array($handler, ['bpmn:startEvent', 'bpmn:task', 'bpmn:event', 'bpmn:endEvent'], TRUE)) {
                    if (!Arrays::isList($process)) {
                        $this->createNode(
                            $topology,
                            $process['@id'] ?? '',
                            $handler,
                            $process['@name'] ?? '',
                            $process['@pipes:pipesType'] ?? '',
                            $nodes,
                            $embedNodes
                        );
                    } else {
                        foreach ($process as $innerProcess) {
                            $this->createNode(
                                $topology,
                                $innerProcess['@id'] ?? '',
                                $handler,
                                $innerProcess['@name'] ?? '',
                                $innerProcess['@pipes:pipesType'] ?? '',
                                $nodes,
                                $embedNodes
                            );
                        }
                    }
                }
            }

            if (isset($data['bpmn:process']['bpmn:sequenceFlow'])) {
                foreach ($data['bpmn:process']['bpmn:sequenceFlow'] as $link) {
                    if (isset($nodes[$link['@sourceRef']]) && isset($embedNodes[$link['@targetRef']])) {
                        $nodes[$link['@sourceRef']]->addNext($embedNodes[$link['@targetRef']]);
                    }
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

        $node = (new Node())
            ->setName($name)
            ->setType($type)
            ->setTopology($topology->getId())
            ->setHandler(Strings::endsWith($handler, 'vent') ? HandlerEnum::EVENT : HandlerEnum::ACTION);
        $this->dm->persist($node);

        $nodes[$id]      = $node;
        $embedNodes[$id] = EmbedNode::from($node);

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

        if (isset($data['visibility'])) {
            $topology->setVisibility($data['visibility']);
        }

        if (isset($data['status'])) {
            $topology->setStatus($data['status']);
        }

        return $topology;
    }

}