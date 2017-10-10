<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 10.10.17
 * Time: 16:16
 */

namespace Hanaboso\PipesFramework\Commons\Docker\Endpoint;

use Hanaboso\PipesFramework\Commons\Docker\DockerClient;

class Containers
{

    /**
     * @var DockerClient
     */
    protected $dockerClient;

    /**
     * Containers constructor.
     *
     * @param DockerClient $dockerClient
     */
    public function __construct(DockerClient $dockerClient)
    {
        $this->dockerClient = $dockerClient;
    }

    public function list(array $params = [])
    {
        $method = "GET";
        $endpointUrl = "http://v{version}/containers/json";
        $headers = [];
        $body = '';

        $this->dockerClient->send();
    }

    public function inspect()
    {

    }

}
