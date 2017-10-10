<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\LastSync;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\LastSyncRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;

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
     * LastSyncManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param ProcessDto    $dto
     * @param SystemInstall $systemInstall
     * @param string        $nodeName
     *
     * @return LastSync
     * @throws SystemException
     */
    public function getLastSync(ProcessDto $dto, SystemInstall $systemInstall, string $nodeName): LastSync
    {
        if (!array_key_exists('node_id', $dto->getHeaders())) {
            throw new SystemException(
                'Missing [node_id] in ProcessDto.',
                SystemException::MISSING_DATA
            );
        }

        $node         = $this->dm->getRepository(Node::class)->findOneBy([
            'id' => GeneratorUtils::denormalizeName($dto->getHeaders()['node_id']),
        ]);
        $topology     = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $node->getTopology()]);
        $topologyName = $topology->getName();

        /** @var LastSyncRepository $repository */
        $repository = $this->dm->getRepository(LastSync::class);
        $lastSync   = $repository->getLastSyncTime($systemInstall->getUser(), $topologyName, $nodeName);

        if (!$lastSync) {
            $lastSync = $this->createLastSync($systemInstall, $nodeName, $topologyName);
        }

        if ($systemInstall->isSynchronized() && $systemInstall->getSynchronizedTime()) {
            $lastSync->setTimestamp($systemInstall->getSynchronizedTime());
        }

        return $lastSync;
    }

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