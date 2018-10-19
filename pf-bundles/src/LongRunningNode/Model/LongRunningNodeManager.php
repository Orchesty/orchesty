<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesFramework\LongRunningNode\Repository\LongRunningNodeDataRepository;

/**
 * Class LongRunningNodeManager
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Model
 */
class LongRunningNodeManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * LongRunningNodeManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param MessageDto $dto
     * @param array      $auditLogs
     *
     * @return LongRunningNodeData
     */
    public function saveDocument(MessageDto $dto, array $auditLogs): LongRunningNodeData
    {
        /** @var LongRunningNodeDataRepository $repo */
        $repo = $this->dm->getRepository(LongRunningNodeData::class);
        /** @var LongRunningNodeData|null $doc */
        $doc  = $repo->findOneBy(['id' => $dto->getDocId()]);

        if (!$doc) {
            $doc = new LongRunningNodeData();
            $doc->setNodeId($dto->getNodeId())
                ->setTopologyId($dto->getTopologyId())
                ->setProcessId($dto->getProcessId());
            if ($dto->getParentProcess()) {
                $doc->setParentProcess($dto->getParentProcess());
            }
            if ($dto->getUpdatedBy()) {
                $doc->setUpdatedBy($dto->getUpdatedBy());
            }

            $this->dm->persist($doc);
        }
        $doc->setAuditLogs($auditLogs)
            ->setData($dto->getData())
            ->setHeaders($dto->getHeaders());

        $this->dm->flush();

        return $doc;
    }

    /**
     * @param string      $topologyId
     * @param string      $nodeId
     * @param null|string $token
     *
     * @return LongRunningNodeData
     * @throws LongRunningNodeException
     */
    public function getDocument(string $topologyId, string $nodeId, ?string $token = NULL): LongRunningNodeData
    {
        $filter = [
            'topologyId' => $topologyId,
            'nodeId'     => $nodeId,
        ];
        if ($token) {
            $filter['processId'] = $token;
        }

        /** @var LongRunningNodeData|null $doc */
        $doc = $this->dm->getRepository(LongRunningNodeData::class)->findOneBy($filter);
        if (!$doc) {
            throw new LongRunningNodeException(
                sprintf('LongRunningData document not found for TopologyId [%s], NodeId [%s]%s',
                    $topologyId, $nodeId,
                    $token ? sprintf(' ProcessId [%s]', $token) : ''
                ),
                LongRunningNodeException::LONG_RUNNING_DOCUMENT_NOT_FOUND
            );
        }

        return $doc;
    }

}