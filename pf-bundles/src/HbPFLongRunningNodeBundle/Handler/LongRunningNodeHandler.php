<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeFilter;
use MongoException;

/**
 * Class LongRunningNodeHandler
 *
 * @package Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Handler
 */
class LongRunningNodeHandler
{

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
     * @param LongRunningNodeLoader $loader
     * @param LongRunningNodeFilter $filter
     * @param DocumentManager       $dm
     */
    public function __construct(
        LongRunningNodeLoader $loader,
        LongRunningNodeFilter $filter,
        DocumentManager $dm
    )
    {
        $this->loader  = $loader;
        $this->filter  = $filter;
        $this->dm      = $dm;
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
        file_put_contents('/tmp/01', '');
        $service = $this->loader->getLongRunningNode($nodeId);
        $docId   = PipesHeaders::get(LongRunningNodeData::DOCUMENT_ID_HEADER, $headers);
        file_put_contents('/tmp/02', '');
        /** @var LongRunningNodeData|null $doc */
        $doc = $this->dm->find(LongRunningNodeData::class, $docId);
        file_put_contents('/tmp/03', '');

        if (!$doc) {
            file_put_contents('/tmp/003', '');
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