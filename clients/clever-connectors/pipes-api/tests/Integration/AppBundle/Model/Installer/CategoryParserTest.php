<?php
/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.12.17
 * Time: 14:58
 */

namespace Tests\Integration\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Model\Installer\CategoryParser;
use CleverConnectors\AppBundle\Model\Installer\Dto\TopologyFile;
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
        $categoryParser = new CategoryParser($this->dm);
        $categoryParser->addPathMap('*/data', 'My-Cat');
        $categoryParser->addPathMap('folder', 'Folder-Cat');

        $topo = new Topology();
        $file = new TopologyFile('aaa.tplg', '/var/www/aa/data/inner/folder');
        $categoryParser->classifyTopology($topo, $file);
    }

}