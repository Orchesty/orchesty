<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller\Dto;

use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyInstaller\TplgLoader;

/**
 * Class CompareResultDto
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller\Dto
 */
final class CompareResultDto
{

    /**
     * @var array|Topology[]
     */
    private $delete = [];

    /**
     * @var array|TopologyFile[]
     */
    private $create = [];

    /**
     * @var array|UpdateObject[]
     */
    private $update = [];

    /**
     * @param array $topologies
     */
    public function addDelete(array $topologies): void
    {
        $this->delete = array_merge($this->delete, $topologies);
    }

    /**
     * @param TopologyFile $file
     */
    public function addCreate(TopologyFile $file): void
    {
        $this->create[] = $file;
    }

    /**
     * @param UpdateObject $file
     */
    public function addUpdate(UpdateObject $file): void
    {
        $this->update[] = $file;
    }

    /**
     * @return array|Topology[]
     */
    public function getDelete(): array
    {
        return $this->delete;
    }

    /**
     * @return array|TopologyFile[]
     */
    public function getCreate(): array
    {
        return $this->create;
    }

    /**
     * @return array|UpdateObject[]
     */
    public function getUpdate(): array
    {
        return $this->update;
    }

    /**
     * @param bool $create
     * @param bool $update
     * @param bool $delete
     *
     * @return array
     */
    public function toArray(bool $create, bool $update, bool $delete): array
    {
        $ret = [];

        if ($create) {
            $ret['create'] = $this->getArrayFromFiles($this->create);
        }

        if ($update) {
            $ret['update'] = $this->getArrayFromObject($this->update);
        }

        if ($delete) {
            $ret['delete'] = $this->getArrayFromTopologies($this->delete);
        }

        return $ret;
    }

    /**
     * @param array| TopologyFile[] $arr
     *
     * @return array
     */
    private function getArrayFromFiles(array $arr): array
    {
        $ret = [];

        foreach ($arr as $item) {
            $ret[] = TplgLoader::getName($item->getName());
        }

        return $ret;
    }

    /**
     * @param array| Topology[] $arr
     *
     * @return array
     */
    private function getArrayFromTopologies(array $arr): array
    {
        $ret = [];

        foreach ($arr as $item) {
            $ret[] = $item->getName();
        }

        return $ret;
    }

    /**
     * @param array| UpdateObject[] $arr
     *
     * @return array
     */
    private function getArrayFromObject(array $arr): array
    {
        $ret = [];

        foreach ($arr as $item) {
            $ret[] = $item->getTopology()->getName();
        }

        return $ret;
    }

}