<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller;

use Doctrine\ODM\MongoDB\MongoDBException;
use FOS\RestBundle\Decoder\XmlDecoder;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TopologiesComparator
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller
 */
class TopologiesComparator
{

    /**
     * @var array
     */
    private $dirs;

    /**
     * @var TopologyRepository
     */
    private $repository;

    /**
     * TopologiesComparator constructor.
     *
     * @param TopologyRepository $repository
     * @param array              $dirs
     */
    public function __construct(TopologyRepository $repository, array $dirs)
    {
        $this->dirs       = $dirs;
        $this->repository = $repository;
    }

    /**
     * @return CompareResultDto
     * @throws MongoDBException
     * @throws TopologyException
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
     * @return array|SplFileInfo[]
     */
    private function prepareFiles(): array
    {
        $files  = [];
        $loader = new TplgLoader();
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
     * @throws TopologyException
     */
    private function isEqual(Topology $topology, SplFileInfo $file): bool
    {
        $xmlDecoder = new XmlDecoder();
        $newSchema  = TopologySchemaUtils::getSchemaObject($xmlDecoder->decode($file->getContents()));

        return $topology->getContentHash() == TopologySchemaUtils::getIndexHash($newSchema);
    }

}