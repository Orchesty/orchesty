<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\PublishGuard\PublishGuardInterface;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;

/**
 * Class TopologySlotGate
 *
 * Single-purpose service that blocks publishing a topology when the cloud
 * plan's `topologySlots` ceiling has been reached. Invoked from
 * {@see \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::publishTopology}
 * BEFORE the topology-generator is called so that callers always see a clean
 * 409 from the API rather than a half-rolled-out bridge.
 *
 * A "slot" is consumed by every published topology row (`visibility=PUBLIC`,
 * `deleted=false`), regardless of `enabled`. Disabling a topology only stops
 * its start nodes; the bridge container keeps running and the slot is NOT
 * freed. To free a slot the user must decommission (unpublish) or delete the
 * topology version on the Resources page. Republish of an already-public
 * topology is therefore not gated - it does not add a new bridge.
 *
 * The same source of truth, {@see TopologyRepository::getPublishedCount()},
 * powers the Overview slot card and the Resources page so the UI cannot
 * disagree with the publish gate.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Configurator\Model
 */
final class TopologySlotGate implements PublishGuardInterface
{

    /**
     * @var ObjectRepository<Topology>&TopologyRepository
     */
    private TopologyRepository $topologyRepository;

    /**
     * TopologySlotGate constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param int                    $limitTopologySlots
     */
    public function __construct(DatabaseManagerLocator $dml, private readonly int $limitTopologySlots = 0)
    {
        /** @var DocumentManager $dm */
        $dm                       = $dml->getDm();
        $this->topologyRepository = $dm->getRepository(Topology::class);
    }

    /**
     * @param Topology $topology
     *
     * @throws MongoDBException
     * @throws TopologyException when publishing this topology would push the published count past the slot limit.
     */
    public function ensureCanPublish(Topology $topology): void
    {
        if ($this->limitTopologySlots <= 0) {
            return;
        }

        // Republish of an already-public, non-deleted topology re-deploys
        // the same bridge - no new slot is consumed.
        $alreadyConsumesSlot = !$topology->isDeleted()
            && $topology->getVisibility() === TopologyStatusEnum::PUBLIC->value;

        if ($alreadyConsumesSlot) {
            return;
        }

        $published = $this->topologyRepository->getPublishedCount();
        if ($published >= $this->limitTopologySlots) {
            throw new TopologyException(
                sprintf(
                    'Cloud plan topology slot limit reached (%d / %d). Decommission an older topology version on the Resources page or delete an unused topology before publishing a new one.',
                    $published,
                    $this->limitTopologySlots,
                ),
                TopologyException::SLOT_LIMIT_REACHED,
            );
        }
    }

    /**
     * @return bool
     */
    public function isEnforced(): bool
    {
        return $this->limitTopologySlots > 0;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limitTopologySlots;
    }

}
