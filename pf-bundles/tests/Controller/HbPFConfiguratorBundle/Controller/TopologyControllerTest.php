<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\String\Json;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class TopologyControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController
 */
final class TopologyControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologiesAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologies
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testGetTopologies(): void
    {
        $this->createTopologies(4);
        $this->assertResponse(
            __DIR__ . '/data/Topology/getTopologiesRequest.json',
            ['_id' => '5e32b04ce99e002a011e0d25']
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologiesAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologies
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testGetTopologiesErr(): void
    {
        $this->createTopologies(4);

        $nodeHandlerMock = self::createMock(TopologyHandler::class);
        $nodeHandlerMock
            ->method('getTopologies')
            ->willThrowException(new MongoDBException());

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.configurator.handler.topology', $nodeHandlerMock);

        $this->assertResponse(
            __DIR__ . '/data/Topology/getTopologiesErrRequest.json',
            ['_id' => '5e32b04ce99e002a011e0d25']
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testGetTopology(): void
    {
        $topology = $this->createTopologies()[0];

        $this->assertResponse(
            __DIR__ . '/data/Topology/getTopologyRequest.json',
            ['_id' => '5e32b3de8602642bb3758653'],
            [':id' => $topology->getId()]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologyAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testGetTopologyNotFound(): void
    {
        $this->assertResponse(__DIR__ . '/data/Topology/getTopologyNotFoundRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::createTopologyAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::createTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testCreateTopology(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/Topology/createTopologyRequest.json',
            ['_id' => '5e32b547b7c6da4b0c54ffc3']
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::createTopologyAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::createTopology
     *
     * @throws Exception
     */
    public function testCreateTopologyErr(): void
    {
        $this->assertResponse(__DIR__ . '/data/Topology/createTopologyErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::updateTopologyAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::updateTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testUpdateTopology(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        $this->assertResponse(
            __DIR__ . '/data/Topology/updateTopologyRequest.json',
            ['_id' => '5e32bc7423cab649c23d4913'],
            [':id' => $topology->getId()]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::updateTopologyAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::updateTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testUpdateTopologyNotFound(): void
    {
        $this->assertResponse(__DIR__ . '/data/Topology/updateTopologyNotFoundRequest.json',);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologySchemaAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testGetTopologySchema(): void
    {
        $topology = $this->createTopologies()[0];

        $this->client->request(
            'GET',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId())
        );

        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];

        self::assertEquals(200, $response->status);
        self::assertEquals($topology->getRawBpmn(), $response->content);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologySchemaAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testGetTopologySchemaNotFound(): void
    {
        $this->client->request(
            'GET',
            '/api/topologies/999/schema.bpmn'
        );

        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->persistAndFlush($topology);

        $this->client->request(
            'PUT',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT'       => 'application/xml',
            ],
            $this->getBpmn()
        );

        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaNotFound(): void
    {

        $this->client->request(
            'PUT',
            '/api/topologies/999/schema.bpmn',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT'       => 'application/xml',
            ],
            $this->getBpmn()
        );

        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        $content  = $response->content;
        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaNameNotFound(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->persistAndFlush($topology);

        $this->client->request(
            'PUT',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT'       => 'application/xml',
            ],
            str_replace('name="Start Event"', '', $this->getBpmn())
        );

        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(400, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaTypeNotExist(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->persistAndFlush($topology);

        $this->client->request(
            'PUT',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT'       => 'application/xml',
            ],
            str_replace('pipes:pipesType="custom"', 'pipes:pipesType="Unknown"', $this->getBpmn())
        );

        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode((string) $response->getContent()),
        ];

        self::assertEquals(400, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaCronNotValid(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->persistAndFlush($topology);

        $this->client->request(
            'PUT',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT'       => 'application/xml',
            ],
            str_replace('pipes:cronTime="*/2 * * * *"', 'pipes:cronTime="Unknown"', $this->getBpmn())
        );

        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(400, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     *
     * @throws Exception
     */
    public function testSaveTopologySchema2(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponse(
            __DIR__ . '/data/Topology/saveTopologyRequest.json',
            ['_id' => '5e395287c5317130b67a4e83'],
            [':id' => $topology->getId()]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologySchemaAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveAndGetTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE)
            ->setRawBpmn($this->getBpmn());
        $this->pfd($topology);

        $this->client->request(
            'GET',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId())
        );

        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];
        self::assertEquals(200, $response->status);
        self::assertEquals($this->getBpmn(), $response->content);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::deleteTopologyAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::deleteTopology
     *
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->persistAndFlush($topology);

        $this->mockHandler('deleteTopology', new ResponseDto(200, '', '{}', []));

        $this->assertResponse(__DIR__ . '/data/Topology/deleteTopologyRequest.json', [], [':id' => $topology->getId()]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::deleteTopologyAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::deleteTopology
     *
     * @throws Exception
     */
    public function testDeleteTopologyErr(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->persistAndFlush($topology);

        $this->assertResponse(
            __DIR__ . '/data/Topology/deleteTopologyErrRequest.json',
            [],
            [':id' => $topology->getId()]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getCronTopologiesAction()
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getCronTopologies
     *
     * @throws Exception
     */
    public function testGetCronTopologies(): void
    {
        $this->mockHandler(
            'getCronTopologies',
            [
                'items' => [
                    [
                        'name'            => 'Topology-Node',
                        'time'            => '*/1 * * * *',
                        'topology_status' => TRUE,
                        'topology_id'     => 'Topology ID',
                        'topology'        => 'Topology',
                        'node'            => 'Node',
                    ],
                ],
            ]
        );

        $this->assertResponse(__DIR__ . '/data/Topology/getCronTopologiesRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::publishTopologyAction
     * @throws Exception
     */
    public function testPublishTopologyAction(): void
    {
        $this->mockHandler('publishTopology', new ResponseDto(200, '', '{"success": true}', []));

        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->dm->persist($topology);
        $node = (new Node())->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $this->assertResponse(
            __DIR__ . '/data/Topology/testPublishTopologyRequest.json',
            [],
            [':id' => $topology->getId()]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::publishTopologyAction
     * @throws Exception
     */
    public function testPublishTopologyErrAction(): void
    {
        $this->mockHandler('publishTopology', new TopologyException());

        $this->assertResponse(__DIR__ . '/data/Topology/testPublishTopologyErrRequest.json',);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::cloneTopologyAction
     * @throws Exception
     */
    public function testCloneTopologyAction(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->mockHandler(
            'cloneTopology',
            [
                '_id'    => '999',
                'type'   => 'type',
                'name'   => 'name',
                'descr'  => 'desc',
                'status' => 'status',
            ]
        );

        $this->assertResponse(__DIR__ . '/data/Topology/cloneTopologyRequest.json', [], [':id' => $topology->getId()]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::cloneTopologyAction
     * @throws Exception
     */
    public function testCloneTopologyActionErr(): void
    {
        $this->mockHandler('cloneTopology', new NodeException());

        $this->assertResponse(__DIR__ . '/data/Topology/cloneTopologyErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getCronTopologiesAction()
     * @throws Exception
     */
    public function testGetCronTopologiesErr(): void
    {
        $this->mockHandler('getCronTopologies', new CronException());
        $this->assertResponse(__DIR__ . '/data/Topology/getCronTopologiesErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::testAction
     * @throws Exception
     */
    public function testAction(): void
    {
        $this->mockHandler('runTest', ['test' => 'success']);

        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponse(__DIR__ . '/data/Topology/testActionRequest.json', [], ['id' => $topology->getId()]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::testAction
     * @throws Exception
     */
    public function testActionErr(): void
    {
        $this->mockHandler('runTest', new LockException('Its lock.'));

        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponse(__DIR__ . '/data/Topology/testActionErrRequest.json', [], ['id' => $topology->getId()]);
    }

    /**
     * @param int $count
     *
     * @return Topology[]
     * @throws Exception
     */
    private function createTopologies(int $count = 1): array
    {
        $topologies = [];
        for ($i = 1; $i <= $count; $i++) {
            $topology = (new Topology())
                ->setName(sprintf('name %s', $i))
                ->setDescr(sprintf('descr %s', $i))
                ->setEnabled(TRUE)
                ->setBpmn($this->getBpmnArray())
                ->setRawBpmn($this->getBpmn());
            $this->persistAndFlush($topology);

            $topologies[] = $topology;
        }

        return $topologies;
    }

    /**
     * @return string
     */
    private function getBpmn(): string
    {
        return (string) file_get_contents(sprintf('%s/data/schema.bpmn', __DIR__));
    }

    /**
     * @return mixed[]
     * @throws Exception
     */
    private function getBpmnArray(): array
    {
        return Json::decode((string) file_get_contents(sprintf('%s/data/schema.json', __DIR__)));
    }

    /**
     * @param string $methodName
     * @param mixed  $res
     *
     * @throws Exception
     */
    private function mockHandler(string $methodName, $res): void
    {
        $configuratorHandlerMock = $this->getMockBuilder(TopologyHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($res instanceof Throwable) {
            $configuratorHandlerMock->method($methodName)->willThrowException($res);
        } else {
            $configuratorHandlerMock->method($methodName)->willReturn($res);
        }

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.configurator.handler.topology', $configuratorHandlerMock);
    }

}
