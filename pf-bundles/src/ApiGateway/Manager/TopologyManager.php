<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Node\Embed\EmbedNode;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Nette\Utils\Strings;

/**
 * Class TopologyManager
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Manager
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

            foreach ($data['bpmn:process'] as $key => $process) {
                if (in_array($key, ['bpmn:startEvent', 'bpmn:task', 'bpmn:endEvent'], TRUE)) {
                    $node = (new Node())
                        ->setName($process['@name'])
                        ->setType(TypeEnum::CONNECTOR) //TODO: Currently not part of XML, change it later...
                        ->setTopology($topology->getId())
                        ->setHandler(Strings::endsWith($key, 'Event') ? HandlerEnum::EVENT : HandlerEnum::ACTION);

                    $this->dm->persist($node);
                    $nodes[$process['@id']]      = $node;
                    $embedNodes[$process['@id']] = EmbedNode::from($node);
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