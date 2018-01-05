<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 1:21 PM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Symfony\Component\Yaml\Yaml;

/**
 * Class ComposeBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose
 */
class ComposeBuilder
{

    /**
     * @param Compose $compose
     *
     * @return string
     */
    public function build(Compose $compose): string
    {
        $composeNetworks = [];
        foreach ($compose->getNetworks() as $network) {
            $composeNetworks[$network] = ['external' => TRUE];
        }

        $services = [];
        foreach ($compose->getServices() as $service) {
            $services[$service->getName()]['image'] = $service->getImage();
            ($service->getWorkDir()) ?: $services[$service->getName()]['working_dir'] = $service->getWorkDir();
            ($service->getUser()) ?: $services[$service->getName()]['user'] = $service->getUser();

            foreach ($service->getEnvironments() as $key => $value) {
                $services[$service->getName()]['environment'][$key] = $value;
            }

            foreach ($service->getVolumes() as $volume) {
                $services[$service->getName()]['volumes'][] = $volume;
            }

            foreach ($service->getPorts() as $port) {
                $services[$service->getName()]['ports'][] = $port;
            }

            foreach ($service->getNetworks() as $network) {
                $services[$service->getName()]['networks'][] = $network;
            }

            foreach ($service->getDependsOn() as $dependOn) {
                $services[$service->getName()]['depends_on'][] = $dependOn;
            }

            ($service->getWorkDir()) ?: $services[$service->getName()]['command'] = $service->getCommand();
        }

        $composeData = [
            'version'  => $compose->getVersion(),
            'services' => $services,
        ];

        if (!empty($composeNetworks)) {
            $composeData['networks'] = $composeNetworks;
        }

        return Yaml::dump($composeData, 4, 2);
    }

}