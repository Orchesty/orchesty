<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Enum\StateEnum;
use Hanaboso\PipesPhpSdk\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeFilter;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class LongRunningNodeHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler
 */
final class LongRunningNodeHandler
{

    /**
     * @var LongRunningNodeManager
     */
    private LongRunningNodeManager $manager;

    /**
     * @var LongRunningNodeLoader
     */
    private LongRunningNodeLoader $loader;

    /**
     * @var LongRunningNodeFilter
     */
    private LongRunningNodeFilter $filter;

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

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
     * @param string  $nodeId
     * @param mixed[] $data
     * @param mixed[] $headers
     *
     * @return ProcessDto
     * @throws LongRunningNodeException
     * @throws MongoDBException
     * @throws EnumException
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

        $stopHeader = PipesHeaders::get(PipesHeaders::PF_STOP, $headers);
        $doc->setState($stopHeader ? StateEnum::CANCELED : StateEnum::ACCEPTED);
        $this->dm->flush();
        $this->dm->clear();

        $dto = $service->afterAction($doc, $data);

        if ($stopHeader) {
            $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), $stopHeader);
        }

        return $dto;
    }

    /**
     * @param string $nodeId
     *
     * @return mixed[]
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
     * @param string|null    $nodeId
     *
     * @return mixed[]
     * @throws Exception
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
            'offset' => (($dto->getPage() ?? 1) - 1) * $dto->getLimit(),
            'count'  => count($result),
            'total'  => $count,
            'items'  => $result,
        ];
    }

    /**
     * @param GridRequestDto $dto
     * @param string         $topologyName
     * @param string|null    $nodeName
     *
     * @return mixed[]
     * @throws Exception
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
            'offset' => (($dto->getPage() ?? 1) - 1) * $dto->getLimit(),
            'count'  => count($result),
            'total'  => $count,
            'items'  => $result,
        ];
    }

    /**
     * @return mixed[]
     */
    public function getAllLongRunningNodes(): array
    {
        return $this->loader->getAllLongRunningNodes();
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws LongRunningNodeException
     * @throws MongoDBException
     */
    public function updateLongRunningNode(string $id, array $data): array
    {
        /** @var LongRunningNodeData|null $node */
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
