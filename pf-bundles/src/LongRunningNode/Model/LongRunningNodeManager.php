<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Enum\StateEnum;
use Hanaboso\PipesFramework\LongRunningNode\Exception\LongRunningNodeException;

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
     * @param LongRunningNodeData $doc
     *
     * @return LongRunningNodeData
     * @throws LongRunningNodeException
     */
    public function saveDocument(LongRunningNodeData $doc): LongRunningNodeData
    {
        $copy = $this->getDocument($doc->getTopologyName(), $doc->getNodeName(), $doc->getProcessId());

        if ($copy) {
            $copy->setAuditLogs(array_merge($copy->getAuditLogs(), $doc->getAuditLogs()))
                ->setData($doc->getData())
                ->setHeaders($doc->getHeaders())
                ->setParentProcess($doc->getParentProcess())
                ->setState(StateEnum::PENDING)
                ->setUpdatedBy($doc->getUpdatedBy());
        } else {
            $copy = $doc;
            $this->dm->persist($copy);
        }
        $this->dm->flush();

        return $copy;
    }

    /**
     * @param string      $topologyName
     * @param string      $nodeName
     * @param null|string $token
     *
     * @return LongRunningNodeData|null
     */
    public function getDocument(string $topologyName, string $nodeName, ?string $token = NULL): ?LongRunningNodeData
    {
        $filter = [
            'topologyName' => $topologyName,
            'nodeName'     => $nodeName,
        ];
        if ($token) {
            $filter['processId'] = $token;
        }

        return $this->dm->getRepository(LongRunningNodeData::class)->findOneBy($filter);
    }

}