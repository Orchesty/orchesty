<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 8.10.17
 * Time: 22:01
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;

class VolumePathDefinition
{

    protected $projectSourcePath;

    /**
     * @var Topology
     */
    protected $topology;

    /**
     * VolumePathDefinition constructor.
     *
     * @param Topology    $topology
     * @param string|null $projectSourcePath
     */
    public function __construct(Topology $topology, ?string $projectSourcePath = NULL)
    {
        $this->projectSourcePath = $projectSourcePath;
        $this->topology          = $topology;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    public function getSourceVolume(string $file): string
    {
        if ($this->projectSourcePath) {
            $srcVolume = sprintf(
                '%s/../topology/%s', $this->projectSourcePath,
                GeneratorUtils::normalizeName($this->topology->getId(), $this->topology->getName())
            );
        } else {
            $srcVolume = '.';
        }

        return sprintf('%s/%s', $srcVolume, $file);
    }

}
