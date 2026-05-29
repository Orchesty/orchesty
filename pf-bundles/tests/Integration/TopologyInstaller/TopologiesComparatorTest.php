<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\TopologyInstaller;

use Exception;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;
use Hanaboso\PipesFramework\TopologyInstaller\TopologiesComparator;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologiesComparatorTest
 *
 * @package PipesFrameworkTests\Integration\TopologyInstaller
 */
#[CoversClass(TopologiesComparator::class)]
#[CoversClass(CompareResultDto::class)]
#[CoversClass(TopologySchemaUtils::class)]
final class TopologiesComparatorTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testCompare(): void
    {
        $sdkUrlMap = [];

        $topology = new Topology();
        $topology
            ->setName('file')
            ->setJson($this->loadJson('file.tplg.json', TRUE))
            ->setContentHash(
                TopologySchemaUtils::getIndexHash(
                    TopologySchemaUtils::getSchemaObjectFromJson($this->loadJson('file.tplg.json', TRUE), $sdkUrlMap),
                ),
            )
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);
        $this->dm->persist($topology);

        $topology3 = new Topology();
        $topology3
            ->setName('file2')
            ->setJson($this->loadJson('file2.tplg.json', FALSE))
            ->setContentHash(
                TopologySchemaUtils::getIndexHash(
                    TopologySchemaUtils::getSchemaObjectFromJson($this->loadJson('file2.tplg.json', FALSE), $sdkUrlMap),
                ),
            )
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);
        $this->dm->persist($topology3);

        $topology2 = new Topology();
        $topology2
            ->setName('old-file')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);
        $this->dm->persist($topology2);
        $this->dm->flush();

        $dir = sprintf('%s/data', __DIR__);

        $repo       = $this->dm->getRepository(Topology::class);
        $comparator = new TopologiesComparator($repo, $sdkUrlMap, [$dir], TRUE);

        $result = $comparator->compare();
        $create = $result->getCreate();
        $update = $result->getUpdate();
        $delete = $result->getDelete();
        self::assertCount(1, $create);
        self::assertCount(1, $update);
        self::assertCount(1, $delete);
    }

    /**
     * @param string $name
     * @param bool   $change
     *
     * @return mixed[]
     */
    private function loadJson(string $name, bool $change): array
    {
        $content = File::getContent(sprintf('%s/data/%s', __DIR__, $name));

        if ($change) {
            $content = str_replace('salesforce-create-contact-mapper', 'salesforce-updaet-contact-mapper', $content);
        }

        return Json::decode($content);
    }

}
