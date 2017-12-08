<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.12.17
 * Time: 14:58
 */

namespace Tests\Integration\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Model\Installer\CategoryParser;
use CleverConnectors\AppBundle\Model\Installer\Dto\TopologyFile;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class CategoryParserTest
 *
 * @package Tests\Integration\AppBundle\Model\Installer
 */
final class CategoryParserTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testClassifyTopology(): void
    {
        $categoryManager = $this->container->get('hbpf.configurator.manager.category');

        $categoryParser = new CategoryParser($this->dm, $categoryManager);
        $categoryParser->addPathMap('*/data', 'System');
        $categoryParser->addPathMap('folder', 'Folder-Cat');
        $categoryParser->addPathMap('new', 'Neu');
        $categoryParser->addExclude('inner');

        $topo = new Topology();
        $this->dm->persist($topo);
        $topo2 = new Topology();
        $this->dm->persist($topo2);
        $this->dm->flush();

        $file = new TopologyFile('aaa.tplg', '/var/www/aa/data/inner/SystemXYZ/SystemXYZ/folder');
        $categoryParser->classifyTopology($topo, $file);

        $file = new TopologyFile('bbb.tplg', '/var/www/aa/data/inner/SystemAAA/new');
        $categoryParser->classifyTopology($topo2, $file);

        $root = $this->dm->getRepository(Category::class)->findBy(['name' => 'System']);

    }

}