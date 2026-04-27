<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFrameworkEnterprise\Configurator\Model\TopologySlotGate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class TopologySlotGateTest
 *
 * Pinpoints the slot publish gate's behaviour without spinning up Mongo.
 * `TopologyRepository` is `final` and gets unfinalised by the
 * `FinalMockExtension` configured in the project's phpunit.xml.dist, so
 * mocking the repo is enough to drive every branch deterministically.
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Configurator\Model
 */
#[CoversClass(TopologySlotGate::class)]
final class TopologySlotGateTest extends TestCase
{

    public function testUnlimitedAlwaysPasses(): void
    {
        $repo = $this->createMock(TopologyRepository::class);
        $repo->expects(self::never())->method('getPublishedCount');

        $gate = $this->buildGate($repo, 0);

        $gate->ensureCanPublish($this->newTopology(TopologyStatusEnum::DRAFT->value));
        self::assertFalse($gate->isEnforced());
        self::assertSame(0, $gate->getLimit());
    }

    public function testNewTopologyBelowLimitPasses(): void
    {
        $repo = $this->createMock(TopologyRepository::class);
        $repo->expects(self::once())->method('getPublishedCount')->willReturn(2);

        $this->buildGate($repo, 5)->ensureCanPublish(
            $this->newTopology(TopologyStatusEnum::DRAFT->value),
        );
    }

    public function testNewTopologyAtLimitThrowsConflict(): void
    {
        $repo = $this->createMock(TopologyRepository::class);
        $repo->expects(self::once())->method('getPublishedCount')->willReturn(5);

        $gate = $this->buildGate($repo, 5);

        try {
            $gate->ensureCanPublish($this->newTopology(TopologyStatusEnum::DRAFT->value));
            self::fail('Expected TopologyException to be thrown');
        } catch (TopologyException $e) {
            self::assertSame(TopologyException::SLOT_LIMIT_REACHED, $e->getCode());
            self::assertStringContainsString('5 / 5', $e->getMessage());
            self::assertStringContainsString('Decommission', $e->getMessage());
        }
    }

    public function testRepublishOfPublicEnabledTopologyPasses(): void
    {
        $repo = $this->createMock(TopologyRepository::class);
        // No need to look at the slot count - the row already occupies a slot.
        $repo->expects(self::never())->method('getPublishedCount');

        $topology = $this->newTopology(TopologyStatusEnum::PUBLIC->value, TRUE);
        $this->buildGate($repo, 1)->ensureCanPublish($topology);
    }

    public function testRepublishOfPublicDisabledTopologyPasses(): void
    {
        // Regression: pre-fix this branch threw because the gate keyed off
        // `isEnabled`. A disabled-but-public topology still occupies a slot
        // (its bridge keeps running) so republish/redeploy must not be gated.
        $repo = $this->createMock(TopologyRepository::class);
        $repo->expects(self::never())->method('getPublishedCount');

        $topology = $this->newTopology(TopologyStatusEnum::PUBLIC->value, FALSE);
        $this->buildGate($repo, 1)->ensureCanPublish($topology);
    }

    public function testDeletedPublicRowDoesNotShortCircuit(): void
    {
        // A soft-deleted row does not occupy a slot anymore, so re-publishing
        // it must go through the normal limit check.
        $repo = $this->createMock(TopologyRepository::class);
        $repo->expects(self::once())->method('getPublishedCount')->willReturn(0);

        $topology = $this->newTopology(TopologyStatusEnum::PUBLIC->value, TRUE);
        $topology->setDeleted(TRUE);

        $this->buildGate($repo, 1)->ensureCanPublish($topology);
    }

    private function buildGate(TopologyRepository $repo, int $limit): TopologySlotGate
    {
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        $locator = $this->createMock(DatabaseManagerLocator::class);
        $locator->method('getDm')->willReturn($dm);

        return new TopologySlotGate($locator, $limit);
    }

    private function newTopology(string $visibility, bool $enabled = FALSE): Topology
    {
        $topology = new Topology();
        $topology
            ->setName('demo')
            ->setVisibility($visibility)
            ->setEnabled($enabled);

        return $topology;
    }

}
