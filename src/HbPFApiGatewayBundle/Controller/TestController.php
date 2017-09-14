<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler\StartingPointHandler;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\GeneratorFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.test")
 */
class TestController extends FOSRestController
{

    private const FILE_NAME = 'topology.txt';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var StartingPointHandler
     */
    private $startingPointHandler;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * TestController constructor.
     *
     * @param DocumentManager      $dm
     * @param StartingPointHandler $startingPointHandler
     * @param string               $rootDir
     */
    public function __construct(DocumentManager $dm, StartingPointHandler $startingPointHandler, string $rootDir)
    {
        $this->dm                   = $dm;
        $this->startingPointHandler = $startingPointHandler;
        $this->rootDir = $rootDir . '/topology';
    }

    /**
     * @Route("/test/topology/generate")
     * @Method({"POST"})
     *
     *
     * @return Response
     */
    public function runAction(): Response
    {
        $this->generateTopology();

        return $this->handleView($this->view([], 200, []));
    }

    /**
     *
     */
    public function generateTopology(): void
    {
        if (file_exists($this->rootDir . '/' . self::FILE_NAME)) {
            $topologyId = file_get_contents($this->rootDir . '/' . self::FILE_NAME);

            $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

            $nodes = $this->dm->getRepository(Node::class)->findBy([
                'topology' => $topologyId,
            ]);

            $generatorFactory = new GeneratorFactory($this->rootDir, 'demo_default');
            $generator        = $generatorFactory->create();

            $generator->generate($topology, $nodes);
        } else {
            $topology = new Topology();
            $topology->setName('topology');
            $this->dm->persist($topology);
            $this->dm->flush();

            $node1 = new Node();
            $node1
                ->setName('null')
                ->setType(TypeEnum::CUSTOM)
                ->setTopology($topology->getId());
            $this->dm->persist($node1);

            $node2 = new Node();
            $node2
                ->setName('null')
                ->setType(TypeEnum::CUSTOM)
                ->setTopology($topology->getId());
            $this->dm->persist($node2);

            $node3 = new Node();
            $node3
                ->setName('null')
                ->setType(TypeEnum::CUSTOM)
                ->setTopology($topology->getId());
            $this->dm->persist($node3);

            $node4 = new Node();
            $node4
                ->setName('null')
                ->setType(TypeEnum::CUSTOM)
                ->setTopology($topology->getId());
            $this->dm->persist($node4);

            $node5 = new Node();
            $node5
                ->setName('null')
                ->setType(TypeEnum::CUSTOM)
                ->setTopology($topology->getId());
            $this->dm->persist($node5);

            $node6 = new Node();
            $node6
                ->setName('null')
                ->setType(TypeEnum::CUSTOM)
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

            file_put_contents($this->rootDir . '/' . self::FILE_NAME, $topology->getId());

            $generatorFactory = new GeneratorFactory($this->rootDir, 'demo_default');
            $generator        = $generatorFactory->create();

            $generator->generate($topology, $nodes);
        }

        $this->startingPointHandler->run($topology->getId(), $nodes[0]->getId());
    }

}
