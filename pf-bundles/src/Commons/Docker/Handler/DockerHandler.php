<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 12:40
 */

namespace Hanaboso\PipesFramework\Commons\Docker\Handler;

use Hanaboso\PipesFramework\Commons\Docker\Docker;
use Hanaboso\PipesFramework\Commons\Docker\Endpoint\Containers;

/**
 * Class DockerHandler
 *
 * @package Hanaboso\PipesFramework\Commons\Docker\Handler
 */
class DockerHandler
{

    /**
     * @var Docker
     */
    protected $docker;

    /**
     * DockerHandler constructor.
     *
     * @param Docker $docker
     */
    public function __construct(Docker $docker)
    {
        $this->docker = $docker;
    }

    /**
     * @param string      $projectName
     *
     * @param null|string $status
     *
     * @return array
     */
    public function getTopologyInfo(string $projectName, ?string $status = NULL): array
    {
        /** @var Containers $containers */
        $containers = $this->docker->getEndpoint(Docker::COINTAINERS);

        $filters = [
            'label' =>
                [
                    0 => 'com.docker.compose.project=' . $projectName,
                ],
        ];

        if ($status) {
            $filters['status'] = [0 => $status];
        }

        return $containers->list([], $filters);
    }

}
