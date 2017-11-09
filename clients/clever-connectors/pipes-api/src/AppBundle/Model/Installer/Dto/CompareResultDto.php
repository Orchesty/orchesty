<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 8.11.17
 * Time: 16:29
 */

namespace CleverConnectors\AppBundle\Model\Installer\Dto;

use CleverConnectors\AppBundle\Model\Installer\TplgLoader;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class CompareResultDto
 *
 * @package CleverConnectors\AppBundle\Model\Installer\Dto
 */
final class CompareResultDto
{

    /**
     * @var array|Topology[]
     */
    private $delete = [];

    /**
     * @var array|SplFileInfo
     */
    private $create = [];

    /**
     * @var array|UpdateObject
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
     * @param SplFileInfo $file
     */
    public function addCreate(SplFileInfo $file): void
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
     * @return array|SplFileInfo[]
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
            $ret['update'] = $this->getArrayFromUpdateObj($this->update);
        }

        if ($delete) {
            $ret['delete'] = $this->getArrayFromTopologies($this->delete);
        }

        return $ret;
    }

    /**
     * --------------------------------------- HELPERS ------------------------------
     */

    /**
     * @param array| SplFileInfo[] $arr
     *
     * @return array
     */
    private function getArrayFromFiles(array $arr): array
    {
        $ret = [];

        foreach ($arr as $item) {
            $ret[] = TplgLoader::getName($item);
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
    private function getArrayFromUpdateObj(array $arr): array
    {
        $ret = [];

        foreach ($arr as $item) {
            $ret[] = $item->getTopology()->getName();
        }

        return $ret;
    }

}