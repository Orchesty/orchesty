<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use Hanaboso\RestBundle\Exception\XmlDecoderException;
use Hanaboso\RestBundle\Model\Decoder\XmlDecoder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TopologiesComparator
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller
 */
class TopologiesComparator
{

    /**
     * @var mixed[]
     */
    private $dirs;

    /**
     * @var TopologyRepository
     */
    private $repository;

    /**
     * @var XmlDecoder
     */
    private XmlDecoder $decoder;

    /**
     * TopologiesComparator constructor.
     *
     * @param TopologyRepository $repository
     * @param XmlDecoder         $decoder
     * @param mixed[]            $dirs
     */
    public function __construct(TopologyRepository $repository, XmlDecoder $decoder, array $dirs)
    {
        $this->dirs       = $dirs;
        $this->repository = $repository;
        $this->decoder    = $decoder;
    }

    /**
     * @return CompareResultDto
     * @throws MongoDBException
     * @throws TopologyException
     * @throws XmlDecoderException
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
     * @return mixed[]|SplFileInfo[]
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
     * @throws XmlDecoderException
     */
    private function isEqual(Topology $topology, SplFileInfo $file): bool
    {
        $newSchema = TopologySchemaUtils::getSchemaObject($this->decoder->decode($file->getContents()));

        return $topology->getContentHash() == TopologySchemaUtils::getIndexHash($newSchema);
    }

}
