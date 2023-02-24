<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class NodeControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController
 */
final class NodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::getNodesAction
     *
     * @throws Exception
     */
    public function testGetNodesAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/NodeController/getNodesRequest.json',
            ['_id' => '123456789', 'topology_id' => '123456789'],
            [':id' => $this->createTopology()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::getNodeAction
     *
     * @throws Exception
     */
    public function testGetNodeAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/NodeController/getNodeRequest.json',
            ['_id' => '123456789', 'topology_id' => '123456789'],
            [':id' => $this->createNode()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::updateNodeAction
     *
     * @throws Exception
     */
    public function testUpdateNodeActionEnable(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/NodeController/updateNodeRequest.json',
            ['_id' => '123456789', 'topology_id' => '123456789'],
            [':id' => $this->createNode()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::updateNodeAction
     *
     * @throws Exception
     */
    public function testUpdateNodeActionCron(): void
    {

        $cron = self::createPartialMock(CronManager::class, ['upsert']);
        $cron->method('upsert')->willReturn(new ResponseDto(200, '', '', []));
        self::getContainer()->set('hbpf.cron.manager', $cron);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/NodeController/updateNodeCronRequest.json',
            ['_id' => '123456789', 'topology_id' => '123456789'],
            [':id' => $this->createNode()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::listNodesNamesAction
     *
     * @throws Exception
     */
    public function testListNodesNamesAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/NodeController/listNodesNamesRequest.json');
    }

    /**
     * @return Topology
     * @throws Exception
     */
    private function createTopology(): Topology
    {
        $topology = new Topology();

        $this->pfd($topology);
        $this->createNode()->setTopology($topology->getId());
        $this->dm->flush();

        return $topology;
    }

    /**
     * @return Node
     * @throws Exception
     */
    private function createNode(): Node
    {
        $node = new Node();
        $node
            ->setType(TypeEnum::CRON->value)
            ->setTopology('1');

        $this->pfd($node);

        return $node;
    }

}
