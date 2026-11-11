<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\Utils\String\Json;
use JsonException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TopologiesComparator
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller
 */
final class TopologiesComparator
{

    /**
     * TopologiesComparator constructor.
     *
     * @param TopologyRepository $repository
     * @param mixed[]            $sdkUrlMap
     * @param mixed[]            $dirs
     * @param bool               $checkInfiniteLoop
     */
    public function __construct(
        private TopologyRepository $repository,
        private array $sdkUrlMap,
        private array $dirs,
        private bool $checkInfiniteLoop,
    )
    {
    }

    /**
     * @return CompareResultDto
     * @throws JsonException
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
                if (!$this->isEqual($db[$name], $file)) {
                    $result->addUpdate(new UpdateObject($db[$name], TopologyFile::from($file)));
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
     * @return mixed[]
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
     * @throws JsonException
     * @throws TopologyException
     */
    private function isEqual(Topology $topology, SplFileInfo $file): bool
    {
        $data      = Json::decode($file->getContents());
        $newSchema = TopologySchemaUtils::getSchemaObjectFromJson($data, $this->sdkUrlMap);

        return $topology->getContentHash() == TopologySchemaUtils::getIndexHash($newSchema, $this->checkInfiniteLoop);
    }

}
