<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 8.9.17
 * Time: 11:52
 */

namespace Tests\Unit\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\GeneratorFactory;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinitionFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class GeneratorFactoryTest
 *
 * @package Tests\Unit\TopologyGenerator\DockerCompose
 */
class GeneratorFactoryTest extends TestCase
{

    /**
     * @covers GeneratorFactory::create()
     */
    public function testCreate(): void
    {
        $generatorFactory = new GeneratorFactory(__DIR__, 'demo', new VolumePathDefinitionFactory(), 'cc', 'swarm');

        $this->assertInstanceOf(Generator::class, $generatorFactory->create());
    }

}