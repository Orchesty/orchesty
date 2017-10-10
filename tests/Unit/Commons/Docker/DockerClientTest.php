<?php
/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 10.10.17
 * Time: 13:34
 */

namespace Tests\Unit\Commons\Docker;

use Hanaboso\PipesFramework\Commons\Docker\DockerClient;
use PHPUnit\Framework\TestCase;

class DockerClientTest extends TestCase
{

    public function testCreateClient()
    {
        new DockerClient();
    }

}