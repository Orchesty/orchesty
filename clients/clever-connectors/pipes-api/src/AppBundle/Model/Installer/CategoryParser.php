<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.12.17
 * Time: 14:40
 */

namespace CleverConnectors\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Model\Installer\Dto\TopologyFile;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use RuntimeException;

/**
 * Class CategoryParser
 *
 * @package CleverConnectors\AppBundle\Model\Installer
 */
class CategoryParser
{

    /**
     * @var array
     */
    private $pathMaps = [];

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * CategoryParser constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param string $path
     * @param string $category
     */
    public function addPathMap(string $path, string $category): void
    {
        $this->pathMaps[$path] = $category;
        ksort($this->pathMaps);
    }

    public function classifyTopology(Topology $topology, TopologyFile $file)
    {
        $categories = $this->getCategories($file);

        $parentCat = '';
        foreach ($categories as $category) {
            // @TODO
            // find parent cat in db if is set
            // find actual cat in db || in not exist create it || if exist check parent
            // set cat into topo
        }

    }

    /**
     * @param TopologyFile $file
     *
     * @return array
     */
    private function getCategories(TopologyFile $file): array
    {
        $cats = [];

        $filePath = $this->getParsedPath($file->getPath());
        $this->removeFileName($filePath, $file);
        foreach ($this->pathMaps as $path => $alias) {
            $mapPath = $this->getParsedPath($path);
            foreach ($mapPath as $map) {
                $this->doLoop($map, $mapPath, $filePath, $alias, $cats);
            }
        }

        return $cats;
    }

    /**
     * @param string $map
     * @param array  $mapPath
     * @param array  $filePath
     * @param string $alias
     * @param array  $out
     */
    private function doLoop(string $map, array &$mapPath, array &$filePath, string $alias, array &$out = []): void
    {
        if ($map == '*') {
            $stop = next($mapPath);
            if ($stop == '*') {
                throw new RuntimeException('Char "*" after "*" is not allowed.');
            }
            foreach ($filePath as $value) {
                if ($value !== $stop) {
                    array_shift($filePath);
                } else {
                    break;
                }
            }
        } elseif ($map == reset($filePath)) {
            array_shift($filePath);
            $out[] = $alias;
        } else {
            array_shift($filePath);
            $this->doLoop($map, $mapPath, $filePath, $alias, $out);
        }
    }

    /**
     * @param string $path
     *
     * @return array
     */
    private function getParsedPath(string $path): array
    {
        return array_filter(explode('/', $path));
    }

    /**
     * @param array        $parts
     * @param TopologyFile $file
     */
    private function removeFileName(array &$parts, TopologyFile $file)
    {
        $key = array_search($file->getName(), $parts);
        if ($key !== FALSE) {
            unset($parts[$key]);
        }
    }

}