<?php declare(strict_types=1);

namespace Tests\Integration\TopologyInstaller;

use Exception;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject;
use Hanaboso\PipesFramework\TopologyInstaller\TopologiesComparator;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologiesComparatorTest
 *
 * @package Tests\Integration\TopologyInstaller
 */
final class TopologiesComparatorTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testCompare(): void
    {
        $xmlDecoder = self::$container->get('rest.decoder.xml');
        $topology   = new Topology();
        $topology
            ->setName('file')
            ->setRawBpmn($this->load('file.tplg', TRUE))
            ->setContentHash(
                TopologySchemaUtils::getIndexHash(
                    TopologySchemaUtils::getSchemaObject($xmlDecoder->decode($this->load('file.tplg', TRUE)))
                )
            )
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology);

        $topology3 = new Topology();
        $topology3
            ->setName('file2')
            ->setRawBpmn($this->load('file2.tplg', FALSE))
            ->setContentHash(
                TopologySchemaUtils::getIndexHash(
                    TopologySchemaUtils::getSchemaObject($xmlDecoder->decode($this->load('file2.tplg', FALSE)))
                )
            )
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology3);

        $topology2 = new Topology();
        $topology2
            ->setName('old-file')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology2);
        $this->dm->flush();

        $dir = sprintf('%s/data', __DIR__);
        /** @var TopologyRepository $repo */
        $repo       = $this->dm->getRepository(Topology::class);
        $comparator = new TopologiesComparator($repo, $xmlDecoder, [$dir]);

        $result = $comparator->compare();
        $create = $result->getCreate();
        $update = $result->getUpdate();
        $delete = $result->getDelete();
        self::assertCount(1, $create);
        self::assertCount(1, $update);
        self::assertCount(1, $delete);

        self::assertInstanceOf(TopologyFile::class, reset($create));
        self::assertInstanceOf(UpdateObject::class, reset($update));
    }

    /**
     * @param string $name
     * @param bool   $change
     *
     * @return string
     */
    private function load(string $name, bool $change): string
    {
        $content = (string) file_get_contents(sprintf('%s/data/%s', __DIR__, $name));

        if (!$change) {
            return $content;
        }

        return str_replace('salesforce-create-contact-mapper', 'salesforce-updaet-contact-mapper', $content);
    }

}
