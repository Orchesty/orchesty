<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller\Dto;

use Hanaboso\PipesFramework\Configurator\Document\Topology;

/**
 * Class UpdateObject
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller\Dto
 */
final class UpdateObject
{

    /**
     * @var Topology
     */
    private $topology;

    /**
     * @var TopologyFile
     */
    private $file;

    /**
     * UpdateObject constructor.
     *
     * @param Topology     $topology
     * @param TopologyFile $file
     */
    public function __construct(Topology $topology, TopologyFile $file)
    {
        $this->topology = $topology;
        $this->file     = $file;
    }

    /**
     * @return Topology
     */
    public function getTopology(): Topology
    {
        return $this->topology;
    }

    /**
     * @return TopologyFile
     */
    public function getFile(): TopologyFile
    {
        return $this->file;
    }

}
