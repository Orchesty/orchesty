<?php
/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 10.10.17
 * Time: 13:34
 */

namespace Tests\Unit\Commons\Docker;

use Hanaboso\PipesFramework\Commons\Docker\Docker;
use Hanaboso\PipesFramework\Commons\Docker\DockerClient;
use Hanaboso\PipesFramework\Commons\Docker\Endpoint\Containers;
use PHPUnit\Framework\TestCase;
use Tests\DatabaseTestCaseAbstract;

class DockerClientTest extends DatabaseTestCaseAbstract
{

    public function testCreateClient()
    {
        $this->markTestSkipped();
        $client = $this->container->get('hbpf.commons.docker.docker_client');
//        $client = new DockerClient();

        $docker    = new Docker($client);
        
        /** @var Containers $container*/
        $container = $docker->getEndpoint(Docker::COINTAINERS);

        $filters = [
            'label'  =>
                [
                    0 => 'com.docker.compose.project=pfbundles',
                ],
            'status' =>
                [
                    0 => 'running',
                ],
        ];


        $result = $container->list([], []);
    }

}
