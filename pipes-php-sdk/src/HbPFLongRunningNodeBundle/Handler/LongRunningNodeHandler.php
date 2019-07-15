<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeFilter;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager;
use MongoException;

/**
 * Class LongRunningNodeHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler
 */
class LongRunningNodeHandler
{

    /**
     * @var LongRunningNodeManager
     */
    private $manager;

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
     * @param LongRunningNodeManager $manager
     * @param LongRunningNodeLoader  $loader
     * @param LongRunningNodeFilter  $filter
     * @param DocumentManager        $dm
     */
    public function __construct(
        LongRunningNodeManager $manager,
        LongRunningNodeLoader $loader,
        LongRunningNodeFilter $filter,
        DocumentManager $dm
    )
    {
        $this->manager = $manager;
        $this->loader  = $loader;
        $this->filter  = $filter;
        $this->dm      = $dm;
    }

    /**
     * @param string $nodeId
     * @param array  $data
     * @param array  $headers
     *
     * @return ProcessDto
     * @throws LongRunningNodeException
     */
    public function process(string $nodeId, array $data, array $headers): ProcessDto
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

        $dto = $service->afterAction($doc, $data);

        $stopHeader = PipesHeaders::get(PipesHeaders::PF_STOP, $headers);
        if ($stopHeader) {
            $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), $stopHeader);
        }

        return $dto;
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

    /**
     * @return array
     */
    public function getAllLongRunningNodes(): array
    {
        return $this->loader->getAllLongRunningNodes();
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     * @throws LongRunningNodeException
     */
    public function updateLongRunningNode(string $id, array $data): array
    {
        /** @var LongRunningNodeData|NULL $node */
        $node = $this->dm->find(LongRunningNodeData::class, $id);

        if (!$node) {
            throw new LongRunningNodeException(
                sprintf('LongRunningData document [%s] was not found', $id),
                LongRunningNodeException::LONG_RUNNING_DOCUMENT_NOT_FOUND
            );
        }

        return $this->manager->update($node, $data)->toArray();
    }

}
