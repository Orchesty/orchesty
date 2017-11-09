<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 9.11.17
 * Time: 14:38
 */

namespace CleverConnectors\AppBundle\Model\Installer\Dto;

use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Symfony\Component\Finder\SplFileInfo;

final class UpdateObject
{

    /**
     * @var Topology
     */
    private $topology;

    /**
     * @var SplFileInfo
     */
    private $file;

    /**
     * UpdateObject constructor.
     *
     * @param Topology    $topology
     * @param SplFileInfo $file
     */
    public function __construct(Topology $topology, SplFileInfo $file)
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
     * @return SplFileInfo
     */
    public function getFile(): SplFileInfo
    {
        return $this->file;
    }

}