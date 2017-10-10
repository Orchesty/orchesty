<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\LastSync;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\LastSyncRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;

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
     * @var DocumentRepository|LastSyncRepository
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
     * @param array         $data
     * @param SystemInstall $systemInstall
     * @param string        $nodeName
     *
     * @return LastSync
     * @throws SystemException
     */
    public function getLastSync(array $data, SystemInstall $systemInstall, string $nodeName): LastSync
    {
        if (!array_key_exists('topology', $data) || !array_key_exists('name', $data['topology'])) {
            throw new SystemException('Missing [topology][name] in data.', SystemException::MISSING_DATA);
        }

        $this->dm->clear(LastSync::class);
        $lastSync = $this->repository->getLastSyncTime($systemInstall->getUser(), $data['topology']['name'], $nodeName);

        if (!$lastSync) {
            $lastSync = $this->createLastSync($systemInstall, $nodeName, $data['topology']['name']);
        }

        if ($systemInstall->isSynchronized() && $systemInstall->getSynchronizedTime()) {
            $lastSync->setTimestamp($systemInstall->getSynchronizedTime());
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