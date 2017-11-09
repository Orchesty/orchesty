<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 8.11.17
 * Time: 16:53
 */

namespace Tests\Integration\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Model\Installer\Dto\CompareResultDto;
use CleverConnectors\AppBundle\Model\Installer\TopologiesComparator;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Symfony\Component\Finder\SplFileInfo;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologiesComparatorTest
 *
 * @package Tests\Integration\AppBundle\Model\Installer
 */
final class TopologiesComparatorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testCompare(): void
    {
        $topology = new Topology();
        $topology
            ->setName('file')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology);

        $topology2 = new Topology();
        $topology2
            ->setName('old-file')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology2);
        $this->dm->flush();

        $dir        = sprintf('%s/data', __DIR__);
        $repo       = $this->dm->getRepository(Topology::class);
        $comparator = new TopologiesComparator($repo, [$dir]);

        $result = $comparator->compare();
        self::assertInstanceOf(CompareResultDto::class, $result);
        $create = $result->getCreate();
        $update = $result->getUpdate();
        $delete = $result->getDelete();
        self::assertCount(1, $create);
        self::assertCount(1, $update);
        self::assertCount(1, $delete);

        self::assertInstanceOf(SplFileInfo::class, reset($create));
        self::assertInstanceOf(SplFileInfo::class, reset($update));
        self::assertInstanceOf(Topology::class, reset($delete));
    }

}