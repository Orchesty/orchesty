<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 9.11.17
 * Time: 14:38
 */

namespace CleverConnectors\AppBundle\Model\Installer\Dto;

use Hanaboso\PipesFramework\Configurator\Document\Topology;

/**
 * Class UpdateObject
 *
 * @package CleverConnectors\AppBundle\Model\Installer\Dto
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