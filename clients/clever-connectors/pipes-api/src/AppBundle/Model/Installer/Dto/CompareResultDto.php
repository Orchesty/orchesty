<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 8.11.17
 * Time: 16:29
 */

namespace CleverConnectors\AppBundle\Model\Installer\Dto;

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
     * @var array|SplFileInfo
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
     * @param SplFileInfo $file
     */
    public function addUpdate(SplFileInfo $file): void
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
     * @return array|SplFileInfo
     */
    public function getCreate(): array
    {
        return $this->create;
    }

    /**
     * @return array|SplFileInfo
     */
    public function getUpdate(): array
    {
        return $this->update;
    }

}