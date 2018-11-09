<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeStartingPoint;

/**
 * Class LongRunningNodeHandler
 *
 * @package Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Handler
 */
class LongRunningNodeHandler
{

    /**
     * @var LongRunningNodeStartingPoint
     */
    private $startingPoint;

    /**
     * @var StartingPointHandler
     */
    private $handler;

    /**
     * @var LongRunningNodeLoader
     */
    private $loader;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * LongRunningNodeHandler constructor.
     *
     * @param LongRunningNodeStartingPoint $startingPoint
     * @param StartingPointHandler         $handler
     * @param LongRunningNodeLoader        $loader
     * @param DocumentManager              $dm
     */
    public function __construct(
        LongRunningNodeStartingPoint $startingPoint,
        StartingPointHandler $handler,
        LongRunningNodeLoader $loader,
        DocumentManager $dm
    )
    {
        $this->startingPoint = $startingPoint;
        $this->handler       = $handler;
        $this->loader        = $loader;
        $this->dm            = $dm;
    }

    /**
     * @param string      $topologyName
     * @param string      $nodeName
     * @param array       $data
     * @param null|string $token
     *
     * @return array
     * @throws LongRunningNodeException
     * @throws MongoDBException
     * @throws PipesFrameworkException
     * @throws StartingPointException
     */
    public function run(string $topologyName, string $nodeName, array $data, ?string $token = NULL): array
    {
        $topos = $this->handler->getTopologies($topologyName);
        $c     = 0;
        foreach ($topos as $topo) {
            $node = $this->handler->getNodeByName($nodeName, $topo);
            $this->startingPoint->run($topo, $node, json_encode($data), $token);
            $c++;
        }

        return ['started_topologies' => $c];
    }

    /**
     * @param string $nodeId
     * @param string $data
     * @param array  $headers
     *
     * @return array
     * @throws LongRunningNodeException
     */
    public function process(string $nodeId, string $data, array $headers): array
    {
        $service = $this->loader->getLongRunningNode($nodeId);
        $docId   = PipesHeaders::get(LongRunningNodeData::DOCUMENT_ID_HEADER, $headers);
        /** @var LongRunningNodeData|null $doc */
        $doc = $this->dm->find(LongRunningNodeData::class, $docId);

        if (!$doc) {
            throw new LongRunningNodeException(
                sprintf('LongRunningData document [%s] was not found', $docId),
                LongRunningNodeException::LONG_RUNNING_DOCUMENT_NOT_FOUND
            );
        }

        $service->afterAction($doc, $data);

        return [];
    }

    /**
     * @param string $nodeId
     *
     * @return array
     * @throws LongRunningNodeException
     */
    public function test(string $nodeId): array
    {
        $this->loader->getLongRunningNode($nodeId);

        return [];
    }

    /**
     * @param string      $topologyId
     * @param null|string $nodeId
     *
     * @return array
     */
    public function getTasks(string $topologyId, ?string $nodeId = NULL): array
    {
        $repo   = $this->dm->getRepository(LongRunningNodeData::class);
        $filter = ['topologyId' => $topologyId];

        if ($nodeId) {
            $filter['nodeId'] = $nodeId;
        }

        $res = [];
        /** @var LongRunningNodeData[] $arr */
        $arr = $repo->findBy($filter);
        foreach ($arr as $row) {
            $res[] = $row->toArray();
        }

        return $res;
    }

}