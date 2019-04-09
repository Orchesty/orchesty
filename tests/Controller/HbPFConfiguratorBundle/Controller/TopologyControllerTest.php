<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Nette\Utils\Json;
use stdClass;
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
     */
    public function testGetTopologyNotFound(): void
    {
        $response = $this->sendGet('/api/topologies/999');
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     * @covers TopologyController::createTopologyAction()
     */
    public function testCreateTopology(): void
    {
        $response = $this->sendPost('/api/topologies', [
            'name'    => 'Topology',
            'descr'   => 'Topology',
            'enabled' => TRUE,
        ]);

        self::assertEquals(200, $response->status);
        self::assertEquals('Topology', $response->content->name);
        self::assertEquals('Topology', $response->content->descr);
        self::assertEquals(TRUE, $response->content->enabled);
    }

    /**
     * @covers TopologyController::updateTopologyAction()
     */
    public function testUpdateTopology(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->persistAndFlush($topology);

        $response = $this->sendPut(sprintf('/api/topologies/%s', $topology->getId()), [
            'name'    => 'Topology 2',
            'descr'   => 'Topology 2',
            'enabled' => TRUE,
        ]);

        self::assertEquals(200, $response->status);
        self::assertEquals('Topology 2', $response->content->name);
        self::assertEquals('Topology 2', $response->content->descr);
        self::assertEquals(TRUE, $response->content->enabled);
    }

    /**
     * @covers TopologyController::updateTopologyAction()
     */
    public function testUpdateTopologyNotFound(): void
    {
        $response = $this->sendPut(sprintf('/api/topologies/999'), [
            'name'    => 'Topology 2',
            'descr'   => 'Topology 2',
            'enabled' => TRUE,
        ]);
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     * @covers TopologyController::getTopologySchemaAction()
     * @throws Exception
     */
    public function testGetTopologySchema(): void
    {
        $topology = $this->createTopologies()[0];

        $this->client->request(
            'GET',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId())
        );

        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];

        self::assertEquals(200, $response->status);
        self::assertEquals($topology->getRawBpmn(), $response->content);
    }

    /**
     * @covers TopologyController::getTopologySchemaAction()
     * @throws Exception
     */
    public function testGetTopologySchemaNotFound(): void
    {
        $this->client->request(
            'GET',
            '/api/topologies/999/schema.bpmn'
        );

        $response = $this->returnResponse($this->client->getResponse());
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        $response = $this->returnResponse($this->client->getResponse());
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        $response = $this->returnResponse($this->client->getResponse());
        $content  = $response->content;
        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        $response = $this->returnResponse($this->client->getResponse());
        self::assertEquals(400, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode($response->getContent()),
        ];

        self::assertEquals(400, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
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

        $response = $this->returnResponse($this->client->getResponse());
        self::assertEquals(400, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchemaAction()
     * @covers TopologyController::getTopologySchemaAction()
     */
    public function testSaveAndGetTopologySchema(): void
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

        $this->client->request(
            'GET',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId())
        );

        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];
        self::assertEquals(200, $response->status);
        self::assertEquals($this->getBpmn(), $response->content);
    }

    /**
     * @covers TopologyController::deleteTopologyAction()
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $this->mockHandler('deleteTopology', new ResponseDto(200, '', '', []));

        $this->client->request(
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

        $response = $this->client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
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
        return file_get_contents(sprintf('%s/data/schema.bpmn', __DIR__));
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getBpmnArray(): array
    {
        return Json::decode(file_get_contents(sprintf('%s/data/schema.json', __DIR__)), Json::FORCE_ARRAY);
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

        $this->client->getContainer()->set('hbpf.configurator.handler.topology', $configuratorHandlerMock);
    }

}
