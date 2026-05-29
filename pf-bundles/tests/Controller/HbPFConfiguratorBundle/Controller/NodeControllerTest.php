<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\LockException;
use Exception;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class NodeControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 */
#[CoversClass(NodeController::class)]
#[CoversClass(NodeHandler::class)]
#[CoversClass(NodeManager::class)]
#[AllowMockObjectsWithoutExpectations]
final class NodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetNodes(): void
    {
        $topology = new Topology();
        $this->dm->persist($topology);
        $node = (new Node())->setTopology($topology->getId());
        $this->pfd($node);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Node/getNodesRequest.json',
            [
                'topology_id' => '5e329eb233609f28e8613113',
                '_id' => '5e329eb233609f28e8613114',
            ],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetNode(): void
    {
        $node = new Node();
        $node->setTopology('1');
        $this->pfd($node);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Node/getNodeRequest.json',
            ['_id' => '5e329f9b5ef3694da71d42b3'],
            [':id' => $node->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetNodeNotFound(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Node/getNodeNotFoundRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testGetNodeErr(): void
    {
        $this->prepareNodeHandlerMock();

        $node = new Node();
        $this->pfd($node);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Node/getNodeErrRequest.json',
            [],
            [':id' => $node->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateNode(): void
    {
        $node = new Node();
        $this->pfd($node);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Node/updateNodeRequest.json',
            ['_id' => '5e32a3bf1280c6296f258c83'],
            [':id' => $node->getId()],
        );
    }

    /**
     * @throws Exception
     */
    private function prepareNodeHandlerMock(): void
    {
        $nodeHandlerMock = self::createMock(NodeHandler::class);
        $nodeHandlerMock
            ->method('getNode')
            ->willThrowException(new LockException('Its lock.'));
        $container = $this->client->getContainer();
        $container->set('hbpf.configurator.handler.node', $nodeHandlerMock);
    }

}
