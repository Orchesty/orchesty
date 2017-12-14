<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\LastSync;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\LastSyncRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use LogicException;

/**
 * Class LastSyncManager
 *
 * @package CleverConnectors\AppBundle\Model\LastSync
 */
class LastSyncManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ObjectRepository|LastSyncRepository
     */
    private $repository;

    /**
     * LastSyncManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm         = $dm;
        $this->repository = $this->dm->getRepository(LastSync::class);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $headers
     *
     * @return LastSync
     */
    public function getLastSync(SystemInstall $systemInstall, array $headers): LastSync
    {
        $topologyName = CMHeaders::get(CMHeaders::TOPOLOGY_NAME, $headers) ?? '';
        $nodeName     = CMHeaders::get(CMHeaders::NODE_NAME, $headers) ?? '';

        if (empty($topologyName) || empty($nodeName)) {
            throw new LogicException('Missing topology_name or node_name Header');
        }

        $lastSync = $this->repository->getLastSyncTime($systemInstall->getUser(), $topologyName, $nodeName);

        if (!$lastSync) {
            $lastSync = $this->createLastSync($systemInstall, $nodeName, $topologyName);

            if ($systemInstall->isSynchronized() && $systemInstall->getSynchronizedTime()) {
                $lastSync->setTimestamp($systemInstall->getSynchronizedTime());
            }
        }

        return $lastSync;
    }

    /**
     * @param LastSync $lastSync
     */
    public function updateLastSync(LastSync $lastSync): void
    {
        $this->dm->flush($lastSync);
    }

    /**
     * ------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     * @param string        $node
     * @param string        $topology
     *
     * @return LastSync
     */
    private function createLastSync(SystemInstall $systemInstall, string $node, string $topology): LastSync
    {
        $lastSync = new LastSync();
        $lastSync
            ->setUser($systemInstall->getUser())
            ->setNodeName($node)
            ->setTopologyName($topology);
        $this->dm->persist($lastSync);

        return $lastSync;
    }

}