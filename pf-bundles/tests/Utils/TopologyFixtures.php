<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 4:04 PM
 */

namespace Tests\Utils;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Node\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\GeneratorFactory;
use Tests\ControllerTestCaseAbstract;

/**
 * Class TopologyFixtures
 *
 * @package Tests\Utils
 */
class TopologyFixtures extends ControllerTestCaseAbstract
{

    private const FILE_NAME = 'topology.txt';

    /**
     * @todo temporary test
     */
    public function testCreateTopology(): void
    {
        if (file_exists(__DIR__ . '/' . self::FILE_NAME)) {
            $topologyId = file_get_contents(__DIR__ . '/' . self::FILE_NAME);

            $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

            $nodes = $this->dm->getRepository(Node::class)->findBy([
                'topology' => $topologyId,
            ]);
        } else {
            $topology = new Topology();
            $topology->setName('topology');
            $this->dm->persist($topology);
            $this->dm->flush();

            $node1 = new Node();
            $node1
                ->setName('magento2_customer')
                ->setType(TypeEnum::CONNECTOR)
                ->setTopology($topology->getId());
            $this->dm->persist($node1);

            $node2 = new Node();
            $node2
                ->setName('xml_parser')
                ->setType(TypeEnum::XML_PARSER)
                ->setTopology($topology->getId());
            $this->dm->persist($node2);

            $node3 = new Node();
            $node3
                ->setName('mapper_1')
                ->setType(TypeEnum::MAPPER)
                ->setTopology($topology->getId());
            $this->dm->persist($node3);

            $node4 = new Node();
            $node4
                ->setName('mail')
                ->setType(TypeEnum::EMAIL)
                ->setTopology($topology->getId());
            $this->dm->persist($node4);

            $node5 = new Node();
            $node5
                ->setName('ftp')
                ->setType(TypeEnum::FTP)
                ->setTopology($topology->getId());
            $this->dm->persist($node5);

            $node6 = new Node();
            $node6
                ->setName('api')
                ->setType(TypeEnum::API)
                ->setTopology($topology->getId());
            $this->dm->persist($node6);

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

            $this->dm->flush();

            file_put_contents(__DIR__ . '/' . self::FILE_NAME, $topology->getId());

            $generatorFactory = new GeneratorFactory(__DIR__, 'demo');
            $generator        = $generatorFactory->create();

            $generator->generate($topology, $nodes);
        }

        $this->sendRequest($topology, $nodes);
    }

    /**
     * @param Topology $topology
     * @param array    $nodes
     */
    public function sendRequest(Topology $topology, array $nodes): void
    {
        $this->client->request(
            'POST',
            '/api/run/' . $topology->getId() . '/' . $nodes[0]->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"test":1}'
        );

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

}