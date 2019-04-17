<?php declare(strict_types=1);

namespace Tests\Integration\TopologyInstaller;

use Exception;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyInstaller\CategoryParser;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use RuntimeException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class CategoryParserTest
 *
 * @package Tests\Integration\TopologyInstaller
 */
final class CategoryParserTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testClassifyTopology(): void
    {
        $categoryManager = self::$container->get('hbpf.configurator.manager.category');

        $categoryParser = new CategoryParser($this->dm, $categoryManager);
        $categoryParser->addRoot('System', '*/data');
        $categoryParser->addAlias('System', 'folder', 'Folder-Cat');
        $categoryParser->addAlias('System', 'new', 'neu');
        $categoryParser->addExclude('System', 'inner');

        $categoryParser->addRoot('pipes-topo', '/var/www/html/system/topologies');

        $categoryParser->addRoot('another-topos', '/var/www/html/system/impl');
        $categoryParser->addAlias('another-topos', 'folder', 'just-a-folder');
        $categoryParser->addExclude('another-topos', 'inner');

        $topo = new Topology();
        $this->dm->persist($topo);
        $topo2 = new Topology();
        $this->dm->persist($topo2);
        $topo3 = new Topology();
        $this->dm->persist($topo3);
        $topo4 = new Topology();
        $this->dm->persist($topo4);
        $this->dm->flush();

        $file = new TopologyFile('aaa.tplg', '/var/www/aa/data/inner/SystemXYZ/SystemXYZ/folder');
        $categoryParser->classifyTopology($topo, $file);

        $file = new TopologyFile('bbb.tplg', '/var/www/aa/data/inner/SystemAAA/new');
        $categoryParser->classifyTopology($topo2, $file);

        $file = new TopologyFile('ccc.tplg', '/var/www/html/system/topologies');
        $categoryParser->classifyTopology($topo3, $file);

        $file = new TopologyFile('ddd.tplg', '/var/www/html/system/impl/inner/folder');
        $categoryParser->classifyTopology($topo4, $file);

        // Check Roots
        $root  = $this->dm->getRepository(Category::class)->findOneBy(['name' => 'System']);
        $root2 = $this->dm->getRepository(Category::class)->findOneBy(['name' => 'pipes-topo']);
        $root3 = $this->dm->getRepository(Category::class)->findOneBy(['name' => 'another-topos']);
        self::assertInstanceOf(Category::class, $root);
        self::assertInstanceOf(Category::class, $root2);
        self::assertInstanceOf(Category::class, $root3);

        // Check sub-cat of first root
        $subRoot = $this->dm->getRepository(Category::class)->findOneBy(
            ['name' => 'SystemXYZ', 'parent' => $root->getId()]
        );
        self::assertInstanceOf(Category::class, $subRoot);
        $sub2Root = $this->dm->getRepository(Category::class)->findOneBy(
            ['name' => 'Folder-Cat', 'parent' => $subRoot->getId()]
        );
        self::assertInstanceOf(Category::class, $sub2Root);

        $subRoot = $this->dm->getRepository(Category::class)->findOneBy(
            ['name' => 'SystemAAA', 'parent' => $root->getId()]
        );
        self::assertInstanceOf(Category::class, $subRoot);
        $sub2Root = $this->dm->getRepository(Category::class)->findOneBy(
            ['name' => 'neu', 'parent' => $subRoot->getId()]
        );
        self::assertInstanceOf(Category::class, $sub2Root);

        // Check sub-cat of third root
        $subRoot = $this->dm->getRepository(Category::class)->findOneBy(
            ['name' => 'just-a-folder', 'parent' => $root3->getId()]
        );
        self::assertInstanceOf(Category::class, $subRoot);
    }

    /**
     * @throws Exception
     */
    public function testClassifyTopologyError(): void
    {
        $categoryManager = self::$container->get('hbpf.configurator.manager.category');

        $categoryParser = new CategoryParser($this->dm, $categoryManager);
        $categoryParser->addRoot('System', '*/*');

        $topo = new Topology();
        $this->dm->persist($topo);
        $this->dm->flush();

        $file = new TopologyFile('aaa.tplg', '/var/www/aa/data/inner/SystemXYZ/SystemXYZ/folder');

        $this->expectException(RuntimeException::class);
        $categoryParser->classifyTopology($topo, $file);
    }

}
