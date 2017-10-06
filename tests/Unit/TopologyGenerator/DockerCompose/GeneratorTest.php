<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 11:24 AM
 */

namespace Tests\Unit\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use Hanaboso\PipesFramework\TopologyGenerator\HostMapper;
use PHPUnit\Framework\TestCase;
use Tests\PrivateTrait;

/**
 * Class GeneratorTest
 *
 * @package Tests\Unit\Generator
 */
class GeneratorTest extends TestCase
{

    use PrivateTrait;

    /**
     * Generator::generate
     */
    public function testGenerate(): void
    {
        $topology = new Topology();
        $this->setProperty($topology, 'id', '1');
        $topology->setName('topology');

        $node1 = new Node();
        $this->setProperty($node1, 'id', '1');
        $node1
            ->setName('magento2-customer')
            ->setType(TypeEnum::CONNECTOR);

        $node2 = new Node();
        $this->setProperty($node2, 'id', '2');
        $node2
            ->setName('xml-parser')
            ->setType(TypeEnum::XML_PARSER);

        $node3 = new Node();
        $this->setProperty($node3, 'id', '3');
        $node3
            ->setName('mapper-1')
            ->setType(TypeEnum::MAPPER);

        $node4 = new Node();
        $this->setProperty($node4, 'id', '4');
        $node4
            ->setName('mail')
            ->setType(TypeEnum::EMAIL);

        $node5 = new Node();
        $this->setProperty($node5, 'id', '5');
        $node5
            ->setName('ftp')
            ->setType(TypeEnum::FTP);

        $node6 = new Node();
        $this->setProperty($node6, 'id', '6');
        $node6
            ->setName('api')
            ->setType(TypeEnum::API);

        $node1->addNext(EmbedNode::from($node2));

        $node2->addNext(EmbedNode::from($node3));

        $node3->addNext(EmbedNode::from($node4));
        $node3->addNext(EmbedNode::from($node5));
        $node3->addNext(EmbedNode::from($node6));

        $nodes[] = $node1;
        $nodes[] = $node2;
        $nodes[] = $node3;
        $nodes[] = $node4;
        $nodes[] = $node5;
        $nodes[] = $node6;

        $generator = new Generator(new Environment(), new HostMapper(), __DIR__ . '/output', 'demo_defualt');

        $generator->generate($topology, $nodes);

        $this->assertSame(
            file_get_contents(__DIR__ . '/samples/docker-compose.yml'),
            file_get_contents(
                __DIR__ . '/output/' .
                GeneratorUtils::normalizeName($topology->getId(), $topology->getName()) .
                '/docker-compose.yml'
            )
        );

        $this->assertSame(
            file_get_contents(__DIR__ . '/samples/topology.json'),
            file_get_contents(
                __DIR__ . '/output/' .
                GeneratorUtils::normalizeName($topology->getId(), $topology->getName()) .
                '/topology.json'
            )
        );
    }

}