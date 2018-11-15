<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeFilter;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeStartingPoint;
use MongoException;

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
     * @var LongRunningNodeFilter
     */
    private $filter;

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
     * @param LongRunningNodeFilter        $filter
     * @param DocumentManager              $dm
     */
    public function __construct(
        LongRunningNodeStartingPoint $startingPoint,
        StartingPointHandler $handler,
        LongRunningNodeLoader $loader,
        LongRunningNodeFilter $filter,
        DocumentManager $dm
    )
    {
        $this->startingPoint = $startingPoint;
        $this->handler       = $handler;
        $this->loader        = $loader;
        $this->filter        = $filter;
        $this->dm            = $dm;
    }

    /**
     * @param string      $topologyName
     * @param string      $nodeName
     * @param array       $data
     * @param null|string $token
     * @param bool        $stop
     *
     * @return array
     * @throws LongRunningNodeException
     * @throws MongoDBException
     * @throws PipesFrameworkException
     * @throws StartingPointException
     */
    public function run(
        string $topologyName,
        string $nodeName,
        array $data,
        ?string $token = NULL,
        bool $stop = FALSE
    ): array
    {
        $topos = $this->handler->getTopologies($topologyName);
        $c     = 0;
        foreach ($topos as $topo) {
            $node = $this->handler->getNodeByName($nodeName, $topo);
            $this->startingPoint->run($topo, $node, json_encode($data), $token, $stop);
            $c++;
        }

        return ['started_topologies' => $c];
    }

    /**
     * @param string      $topologyId
     * @param string      $nodeId
     * @param array       $data
     * @param null|string $token
     * @param bool        $stop
     *
     * @return array
     * @throws LongRunningNodeException
     * @throws MongoDBException
     * @throws PipesFrameworkException
     * @throws MappingException
     * @throws StartingPointException
     */
    public function runById(
        string $topologyId,
        string $nodeId,
        array $data,
        ?string $token = NULL,
        bool $stop = FALSE
    ): array
    {
        $topo = $this->handler->getTopology($topologyId);
        $node = $this->handler->getNode($nodeId);
        $this->startingPoint->run($topo, $node, json_encode($data), $token, $stop);

        return ['started_topologies' => 1];
    }

    /**
     * @param string $nodeId
     * @param string $data
     * @param array  $headers
     *
     * @return ProcessDto
     * @throws LongRunningNodeException
     */
    public function process(string $nodeId, string $data, array $headers): ProcessDto
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

        $this->dm->remove($doc);
        $this->dm->flush();
        $this->dm->clear();

        return $service->afterAction($doc, $data);
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
     * @param GridRequestDto $dto
     * @param string         $topologyId
     * @param null|string    $nodeId
     *
     * @return array
     * @throws MongoDBException
     * @throws GridException
     * @throws MongoException
     */
    public function getTasksById(GridRequestDto $dto, string $topologyId, ?string $nodeId = NULL): array
    {
        $dto->setAdditionalFilters([LongRunningNodeData::TOPOLOGY_ID => $topologyId]);

        if ($nodeId) {
            $dto->setAdditionalFilters([LongRunningNodeData::NODE_ID => $nodeId]);
        }

        $result = $this->filter->getData($dto)->toArray();
        $count  = $dto->getParamsForHeader()['total'];

        return [
            'limit'  => $dto->getLimit(),
            'offset' => ((int) ($dto->getPage() ?? 1) - 1) * $dto->getLimit(),
            'count'  => count($result),
            'total'  => $count,
            'items'  => $result,
        ];
    }

    /**
     * @param GridRequestDto $dto
     * @param string         $topologyName
     * @param null|string    $nodeName
     *
     * @return array
     * @throws MongoDBException
     * @throws GridException
     * @throws MongoException
     */
    public function getTasks(GridRequestDto $dto, string $topologyName, ?string $nodeName = NULL): array
    {
        $dto->setAdditionalFilters([LongRunningNodeData::TOPOLOGY_NAME => $topologyName]);

        if ($nodeName) {
            $dto->setAdditionalFilters([LongRunningNodeData::NODE_NAME => $nodeName]);
        }

        $result = $this->filter->getData($dto)->toArray();
        $count  = $dto->getParamsForHeader()['total'];

        return [
            'limit'  => $dto->getLimit(),
            'offset' => ((int) ($dto->getPage() ?? 1) - 1) * $dto->getLimit(),
            'count'  => count($result),
            'total'  => $count,
            'items'  => $result,
        ];
    }

}