<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Hanaboso\CommonsBundle\Document\Category;
use Hanaboso\CommonsBundle\Document\Topology;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\CommonsBundle\Repository\CategoryRepository;
use Hanaboso\PipesFramework\Configurator\Model\CategoryManager;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use RuntimeException;

/**
 * Class CategoryParser
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller
 */
class CategoryParser
{

    public const ALL = '*';

    /**
     * @var array
     */
    private $roots = [];

    /**
     * @var array
     */
    private $excludes = [];

    /**
     * @var array
     */
    private $aliases = [];

    /**
     * @var array
     */
    private $pathFromFile = [];

    /**
     * @var array
     */
    private $tmpFilePath = [];

    /**
     * @var array
     */
    private $tmpPath = [];

    /**
     * @var string
     */
    private $matchedRootAlias = '';

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
     * @param string $alias
     * @param string $path
     *
     * @return CategoryParser
     */
    public function addRoot(string $alias, string $path): CategoryParser
    {
        $this->roots[$alias] = $path;

        return $this;
    }

    /**
     * @param string $rootAlias
     * @param string $folder
     *
     * @return CategoryParser
     */
    public function addExclude(string $rootAlias, string $folder): CategoryParser
    {
        if (array_key_exists($rootAlias, $this->roots)) {
            $this->excludes[$rootAlias][] = $folder;
        }

        return $this;
    }

    /**
     * @param string $rootAlias
     * @param string $folder
     * @param string $alias
     *
     * @return CategoryParser
     */
    public function addAlias(string $rootAlias, string $folder, string $alias): CategoryParser
    {
        if (array_key_exists($rootAlias, $this->roots)) {
            $this->aliases[$rootAlias][$alias] = $folder;
        }

        return $this;
    }

    /**
     * @param Topology     $topology
     * @param TopologyFile $file
     *
     * @throws CategoryException
     * @throws LockException
     * @throws MappingException
     */
    public function classifyTopology(Topology $topology, TopologyFile $file): void
    {
        $categories = $this->getCategories($file);
        $parent     = '';
        foreach ($categories as $name) {
            /** @var Category $category */
            $category = $this->categoryRepository->findOneBy(['name' => $name]);

            if (!empty($category) && ($category->getParent() == $parent || empty($parent))) {
                $category = $this->categoryManager->updateCategory($category, ['parent' => $parent]);
            } else {
                $category = $this->createCategory($name, $parent);
            }

            $topology->setCategory($category->getId());
            $parent = $category->getId();
        }

        $this->dm->flush();
    }

    /**
     * @param TopologyFile $file
     *
     * @return array
     */
    private function getCategories(TopologyFile $file): array
    {
        $this->pathFromFile = $this->getParsedPath($file->getPath(TRUE));
        $this->matchRoot();
        $this->removeExcluded();
        $this->setAliases();
        array_unshift($this->tmpFilePath, $this->matchedRootAlias);

        return $this->tmpFilePath;
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
     * @return array
     */
    private function matchRoot(): array
    {
        $categories = [];
        foreach ($this->roots as $alias => $path) {
            $this->tmpFilePath = $this->pathFromFile;
            $this->tmpPath     = $this->getParsedPath($path);
            foreach ($this->tmpPath as $key => $part) {
                $isMatch = $this->processRootParts($part);
                if (!$isMatch) {
                    break;
                }
                unset($this->tmpPath[$key]);
            }

            if (empty($this->tmpPath)) {
                $this->matchedRootAlias = $alias;
                $this->tmpFilePath      = array_unique($this->tmpFilePath);
                array_unshift($categories, $alias);
                break;
            }
        }

        return $categories;
    }

    /**
     * @param string $root
     *
     * @return bool
     */
    private function processRootParts(string $root): bool
    {
        if ($root === self::ALL) {
            $this->removePartsOfPath(next($this->tmpPath));
        } elseif ($root === reset($this->tmpFilePath)) {
            array_shift($this->tmpFilePath);
        } else {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @param string $stop
     */
    private function removePartsOfPath(string $stop): void
    {
        $this->checkStopChar($stop);
        $copy = $this->tmpFilePath;
        foreach ($copy as $value) {
            if ($value === $stop) {
                $this->tmpFilePath = $copy;
                break;
            }
            array_shift($copy);
        }
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
     *
     */
    private function removeExcluded(): void
    {
        if (isset($this->excludes[$this->matchedRootAlias])) {
            foreach ($this->excludes[$this->matchedRootAlias] as $exclude) {
                $this->removeElement($this->tmpFilePath, $exclude);
            }
        }
    }

    /**
     * @param array  $array
     * @param string $element
     */
    private function removeElement(array &$array, string $element): void
    {
        $key = array_search($element, $array);
        if ($key !== FALSE) {
            unset($array[$key]);
        }
    }

    /**
     *
     */
    private function setAliases(): void
    {
        if (isset($this->aliases[$this->matchedRootAlias])) {
            foreach ($this->aliases[$this->matchedRootAlias] as $alias => $value) {
                $this->replaceElement($this->tmpFilePath, $value, $alias);
            }
        }
    }

    /**
     * @param array  $array
     * @param string $element
     * @param string $replacement
     */
    private function replaceElement(array &$array, string $element, string $replacement): void
    {
        $key = array_search($element, $array);
        if ($key !== FALSE) {
            $array[$key] = $replacement;
        }
    }

    /**
     * @param string $name
     * @param string $parent
     *
     * @return Category
     * @throws CategoryException
     * @throws LockException
     * @throws MappingException
     */
    private function createCategory(string $name, string $parent): Category
    {
        return $this->categoryManager->createCategory(['name' => $name, 'parent' => $parent ?? NULL]);
    }

}
