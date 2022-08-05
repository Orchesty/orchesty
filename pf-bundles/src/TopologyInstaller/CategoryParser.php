<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\PipesFramework\Configurator\Model\CategoryManager;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use Hanaboso\PipesPhpSdk\Database\Document\Category;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\CategoryRepository;
use RuntimeException;

/**
 * Class CategoryParser
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller
 */
final class CategoryParser
{

    public const ALL = '*';

    /**
     * @var mixed[]
     */
    private array $excludes = [];

    /**
     * @var mixed[]
     */
    private array $aliases = [];

    /**
     * @var mixed[]
     */
    private array $pathFromFile = [];

    /**
     * @var mixed[]
     */
    private array $tmpFilePath = [];

    /**
     * @var mixed[]
     */
    private array $tmpPath = [];

    /**
     * @var string
     */
    private string $matchedRootAlias = '';

    /**
     * @var ObjectRepository<Category>&CategoryRepository
     */
    private CategoryRepository $categoryRepository;

    /**
     * CategoryParser constructor.
     *
     * @param DocumentManager $dm
     * @param CategoryManager $categoryManager
     * @param mixed[]         $roots
     */
    public function __construct(
        private DocumentManager $dm,
        private CategoryManager $categoryManager,
        private array $roots = [],
    )
    {
        $this->categoryRepository = $this->dm->getRepository(Category::class);
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
     * @throws MongoDBException
     */
    public function classifyTopology(Topology $topology, TopologyFile $file): void
    {
        $categories = $this->getCategories($file);
        $parent     = '';
        foreach ($categories as $name) {
            /** @var Category|null $category */
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
     * @return mixed[]
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
     * @return mixed[]
     */
    private function getParsedPath(string $path): array
    {
        return array_filter(explode('/', $path));
    }

    /**
     *
     */
    private function matchRoot(): void
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
        } else if ($root === reset($this->tmpFilePath)) {
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
     * @param mixed[] $array
     * @param string  $element
     */
    private function removeElement(array &$array, string $element): void
    {
        $key = array_search($element, $array, TRUE);
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
     * @param mixed[] $array
     * @param string  $element
     * @param string  $replacement
     */
    private function replaceElement(array &$array, string $element, string $replacement): void
    {
        $key = array_search($element, $array, TRUE);
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
     * @throws MongoDBException
     */
    private function createCategory(string $name, string $parent): Category
    {
        return $this->categoryManager->createCategory(['name' => $name, 'parent' => $parent]);
    }

}
