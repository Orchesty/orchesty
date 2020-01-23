<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\String\Json;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class TopologyControllerTest
 *
 * @package Tests\Controller\HbPFConfiguratorBundle\Controller
 */
final class TopologyControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers TopologyController::getTopologiesAction()
     *
     * @throws Exception
     */
    public function testGetTopologies(): void
    {
        $topologies = $this->createTopologies(4);

        $response = $this->sendGet('/api/topologies?limit=2&offset=1&order_by=name-');

        self::assertEquals(200, $response->status);
        self::assertEquals(1, $response->content->offset);
        self::assertEquals(2, $response->content->limit);
        self::assertEquals(2, $response->content->count);
        self::assertEquals(4, $response->content->total);

        self::assertTopology($topologies[2], (object) $response->content->items[0]);
        self::assertTopology($topologies[1], (object) $response->content->items[1]);
    }

    /**
     * @covers TopologyController::getTopologyAction()
     *
     * @throws Exception
     */
    public function testGetTopology(): void
    {
        $topology = $this->createTopologies()[0];

        $response = $this->sendGet(sprintf('/api/topologies/%s', $topology->getId()));

        self::assertEquals(200, $response->status);
        self::assertTopology($topology, $response->content);
    }

    /**
     * @covers TopologyController::getTopologyAction()
     *
     * @throws Exception
     */
    public function testGetTopologyNotFound(): void
    {
        $response = $this->sendGet('/api/topologies/999');
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @covers TopologyController::createTopologyAction()
     *
     * @throws Exception
     */
    public function testCreateTopology(): void
    {
        $response = $this->sendPost(
            '/api/topologies',
            [
                'name'    => 'Topology',
                'descr'   => 'Topology',
                'enabled' => TRUE,
            ]
        );

        self::assertEquals(200, $response->status);
        self::assertEquals('topology', $response->content->name);
        self::assertEquals('Topology', $response->content->descr);
        self::assertEquals(TRUE, $response->content->enabled);
    }

    /**
     * @covers TopologyController::updateTopologyAction()
     *
     * @throws Exception
     */
    public function testUpdateTopology(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->persistAndFlush($topology);

        $response = $this->sendPut(
            sprintf('/api/topologies/%s', $topology->getId()),
            [
                'name'    => 'Topology 2',
                'descr'   => 'Topology 2',
                'enabled' => TRUE,
            ]
        );

        self::assertEquals(200, $response->status);
        self::assertEquals('topology-2', $response->content->name);
        self::assertEquals('Topology 2', $response->content->descr);
        self::assertEquals(TRUE, $response->content->enabled);
    }

    /**
     * @covers TopologyController::updateTopologyAction()
     *
     * @throws Exception
     */
    public function testUpdateTopologyNotFound(): void
    {
        $response = $this->sendPut(
            sprintf('/api/topologies/999'),
            [
                'name'    => 'Topology 2',
                'descr'   => 'Topology 2',
                'enabled' => TRUE,
            ]
        );
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @covers TopologyController::getTopologySchemaAction()
     *
     * @throws Exception
     */
    public function testGetTopologySchema(): void
    {
        $topology = $this->createTopologies()[0];

        self::$client->request(
            'GET',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId())
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];

        self::assertEquals(200, $response->status);
        self::assertEquals($topology->getRawBpmn(), $response->content);
    }

    /**
     * @covers TopologyController::getTopologySchemaAction()
     *
     * @throws Exception
     */
    public function testGetTopologySchemaNotFound(): void
    {
        self::$client->request(
            'GET',
            '/api/topologies/999/schema.bpmn'
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        $response = $this->returnResponse($response);
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        self::$client->request(
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
        $response = self::$client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaNotFound(): void
    {

        self::$client->request(
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
        $response = self::$client->getResponse();
        $response = $this->returnResponse($response);
        $content  = $response->content;
        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        self::$client->request(
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
        $response = self::$client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(400, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        self::$client->request(
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
        $response = self::$client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode((string) $response->getContent()),
        ];

        self::assertEquals(400, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        self::$client->request(
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
        $response = self::$client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(400, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
     * @covers TopologyController::getTopologySchemaAction()
     *
     * @throws Exception
     */
    public function testSaveAndGetTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->persistAndFlush($topology);

        self::$client->request(
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

        self::$client->request(
            'GET',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId())
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];
        self::assertEquals(200, $response->status);
        self::assertEquals($this->getBpmn(), $response->content);
    }

    /**
     * @covers TopologyController::deleteTopologyAction()
     *
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $this->mockHandler('deleteTopology', new ResponseDto(200, '', '', []));

        self::$client->request(
            'DELETE',
            '/api/topologies/999',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT'       => 'application/xml',
            ],
            $this->getBpmn()
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers TopologyController::getCronTopologiesAction()
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
        $response = $this->sendGet('/api/topologies/cron');

        self::assertEquals(200, $response->status);
        self::assertEquals(
            (object) [
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
            ],
            $response->content
        );
    }

    /**
     * @param Topology $topology
     * @param stdClass $item
     */
    private function assertTopology(Topology $topology, stdClass $item): void
    {
        self::assertEquals($topology->getId(), $item->_id);
        self::assertEquals($topology->getName(), $item->name);
        self::assertEquals($topology->getDescr(), $item->descr);
        self::assertEquals($topology->isEnabled(), $item->enabled);
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
    private function mockHandler(string $methodName, $res = ['test']): void
    {
        $configuratorHandlerMock = $this->getMockBuilder(TopologyHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configuratorHandlerMock->method($methodName)->willReturn($res);

        /** @var ContainerInterface $container */
        $container = self::$client->getContainer();
        $container->set('hbpf.configurator.handler.topology', $configuratorHandlerMock);
    }

}
