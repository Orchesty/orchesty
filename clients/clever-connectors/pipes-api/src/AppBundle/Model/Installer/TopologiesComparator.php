<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 8.11.17
 * Time: 16:12
 */

namespace CleverConnectors\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Model\Installer\Dto\CompareResultDto;
use CleverConnectors\AppBundle\Model\Installer\Dto\TopologyFile;
use CleverConnectors\AppBundle\Model\Installer\Dto\UpdateObject;
use Doctrine\Common\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Symfony\Component\Finder\SplFileInfo;

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
                if (!$this->isEqual($db[$name], $files[$name])) {
                    $result->addUpdate(new UpdateObject($db[$name], TopologyFile::from($files[$name])));
                }
                unset($db[$name]);
            } else {
                $result->addCreate(TopologyFile::from($file));
            }
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
     * @return array|SplFileInfo[]
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
     * @param Topology    $topology
     * @param SplFileInfo $file
     *
     * @return bool
     */
    private function isEqual(Topology $topology, SplFileInfo $file): bool
    {
        return md5($this->removeWhiteSpace($topology->getRawBpmn())) == md5($this->removeWhiteSpace($file->getContents()));
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function removeWhiteSpace(string $string): string
    {
        $string = preg_replace('/[\t\n\r\0\x0B]/', '', $string);
        $string = preg_replace('/([\s])\1+/', ' ', $string);
        $string = trim($string);

        return $string;
    }

    /**
     * @return TplgLoader
     */
    protected function createLoader(): TplgLoader
    {
        return new TplgLoader();
    }

}