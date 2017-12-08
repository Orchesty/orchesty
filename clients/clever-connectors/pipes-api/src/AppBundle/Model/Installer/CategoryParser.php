<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.12.17
 * Time: 14:40
 */

namespace CleverConnectors\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Model\Installer\Dto\TopologyFile;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Category\Model\CategoryManager;
use Hanaboso\PipesFramework\Category\Repository\CategoryRepository;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use RuntimeException;

/**
 * Class CategoryParser
 *
 * @package CleverConnectors\AppBundle\Model\Installer
 */
class CategoryParser
{

    public const ALL = '*';

    /**
     * @var array
     */
    private $pathMaps = [];

    /**
     * @var array
     */
    private $exclude = [];

    /**
     * @var array
     */
    private $filePath = [];

    /**
     * @var array
     */
    private $tmpPath = [];

    /**
     * @var array
     */
    private $tmpMapPath = [];

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CategoryRepository|ObjectRepository
     */
    private $categoryRepository;

    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * CategoryParser constructor.
     *
     * @param DocumentManager $dm
     * @param CategoryManager $categoryManager
     */
    public function __construct(DocumentManager $dm, CategoryManager $categoryManager)
    {
        $this->dm                 = $dm;
        $this->categoryRepository = $this->dm->getRepository(Category::class);
        $this->categoryManager    = $categoryManager;
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

    /**
     * @param string $exclude
     */
    public function addExclude(string $exclude): void
    {
        if (!in_array($exclude, $this->exclude)) {
            $this->exclude[] = $exclude;
        }
    }

    /**
     * @param Topology     $topology
     * @param TopologyFile $file
     */
    public function classifyTopology(Topology $topology, TopologyFile $file): void
    {
        $categories = $this->getCategories($file);
        $parent     = '';
        foreach ($categories as $name) {
            /** @var Category $category */
            $category = $this->categoryRepository->findBy(['name' => $name]);

            if (!empty($category) && ($category->getParent() == $parent || empty($parent))) {
                $category = $this->categoryManager->updateCategory($category, ['parent' => $parent]);
            } else {
                $category = $this->createCategory($name, $parent);
            }

            $topology->setCategory($category->getName());
            $parent = $category->getId();
        }

        $this->dm->flush();
    }

    /**
     * ------------------------------------ HELPERS -----------------------------------------
     */

    /**
     * @param TopologyFile $file
     *
     * @return array
     */
    private function getCategories(TopologyFile $file): array
    {
        $cats = [];

        $this->filePath = $this->getParsedPath($file->getPath());
        $this->removeElement($this->filePath, $file->getName());
        foreach ($this->pathMaps as $path => $alias) {
            $this->tmpPath    = $this->filePath;
            $this->tmpMapPath = $this->getParsedPath($path);
            foreach ($this->tmpMapPath as $map) {
                if (!empty($this->filePath)) {
                    $this->doLoop($map, $cats);
                }
                $this->replaceElement($cats, $map, $alias);
            }

        }

        return $cats;
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
     * @param array  $array
     * @param string $element
     */
    private function removeElement(array &$array, string $element)
    {
        $key = array_search($element, $array);
        if ($key !== FALSE) {
            unset($array[$key]);
        }
    }

    /**
     * @param array  $array
     * @param string $element
     */
    private function replaceElement(array &$array, string $element, string $replacement)
    {
        $key = array_search($element, $array);
        if ($key !== FALSE) {
            $array[$key] = $replacement;
        }
    }

    /**
     * @param string $map
     * @param array  $out
     */
    private function doLoop(string $map, array &$out = []): void
    {
        if ($map === self::ALL) {
            $this->removePartsOfPath(next($this->tmpMapPath));
        } elseif ($map === reset($this->tmpPath)) {
            $key = array_shift($this->tmpPath);
            $this->removeElement($this->filePath, $key);
            $this->addToOutput($out, $key);
        } else {
            $key = array_shift($this->tmpPath);
            if (!in_array($key, $this->exclude)) {
                $this->addToOutput($out, $key);
            }

            if (!empty($this->tmpPath)) {
                $this->doLoop($map, $out);
            }
        }
    }

    /**
     * @param string $stop
     */
    private function removePartsOfPath(string $stop): void
    {
        $this->checkStopChar($stop);
        foreach ($this->tmpPath as $value) {
            if ($value !== $stop) {
                array_shift($this->tmpPath);
            } else {
                break;
            }
        }
        $this->filePath = $this->tmpPath;
        $this->removeElement($this->filePath, $stop);
    }

    /**
     * @param string $stop
     */
    private function checkStopChar(string $stop): void
    {
        if ($stop === self::ALL) {
            throw new RuntimeException('Char "*" after "*" is not allowed.');
        }
    }

    /**
     * @param array  $out
     * @param string $in
     */
    private function addToOutput(array &$out, string $in): void
    {
        if (!in_array($in, $out)) {
            $out[] = $in;
        }
    }

    /**
     * @param string $name
     * @param string $parent
     *
     * @return Category
     */
    private function createCategory(string $name, string $parent): Category
    {
        return $this->categoryManager->createCategory(['name' => $name, 'parent' => $parent ?? NULL]);
    }

}