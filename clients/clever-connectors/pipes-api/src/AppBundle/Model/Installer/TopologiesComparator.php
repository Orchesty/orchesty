<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 8.11.17
 * Time: 16:12
 */

namespace CleverConnectors\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Model\Installer\Dto\CompareResultDto;
use Doctrine\Common\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;

/**
 * Class TopologiesComparator
 *
 * @package CleverConnectors\AppBundle\Model\Installer
 */
class TopologiesComparator
{

    /**
     * @var array
     */
    private $dirs;

    /**
     * @var ObjectRepository|TopologyRepository
     */
    private $repository;

    /**
     * TopologiesComparator constructor.
     *
     * @param TopologyRepository|ObjectRepository $repository
     * @param array                               $dirs
     */
    public function __construct(TopologyRepository $repository, array $dirs)
    {
        $this->dirs       = $dirs;
        $this->repository = $repository;
    }

    /**
     * @return CompareResultDto
     */
    public function compare(): CompareResultDto
    {
        $files  = $this->prepareFiles();
        $db     = $this->repository->getTopologies();
        $result = new CompareResultDto();

        foreach ($files as $name => $file) {
            if (array_key_exists($name, $db)) {
                $result->addUpdate($file);
                unset($db[$name]);
            } else {
                $result->addCreate($file);
            }
            unset($files[$name]);
        }

        sort($db);
        $result->addDelete($db);
        unset($db);

        return $result;
    }

    /**
     * ------------------------------------- HELPERS --------------------------------
     */

    /**
     * @return array
     */
    private function prepareFiles(): array
    {
        $files  = [];
        $loader = $this->createLoader();
        foreach ($this->dirs as $dir) {
            $files = array_merge($files, $loader->load($dir));
        }

        return $files;
    }

    /**
     * @return TplgLoader
     */
    protected function createLoader(): TplgLoader
    {
        return new TplgLoader();
    }

}