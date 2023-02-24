<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller\Dto;

use Hanaboso\PipesPhpSdk\Database\Document\Topology;

/**
 * Class UpdateObject
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller\Dto
 */
final class UpdateObject
{

    /**
     * UpdateObject constructor.
     *
     * @param Topology     $topology
     * @param TopologyFile $file
     */
    public function __construct(private Topology $topology, private TopologyFile $file)
    {
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
