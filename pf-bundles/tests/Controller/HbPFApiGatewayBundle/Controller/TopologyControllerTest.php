<?php declare(strict_types=1);

namespace Tests\Controller\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\Commons\Exception\TopologyException;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\TopologyController;
use Nette\Utils\Json;
use stdClass;
use Tests\ControllerTestCaseAbstract;

/**
 * Class TopologyControllerTest
 *
 * @package Tests\Controller\HbPFApiGatewayBundle\Controller
 */
final class TopologyControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers TopologyController::getTopologiesAction()
     */
    public function testGetTopologies(): void
    {
        $topologies = $this->createTopologies(4);

        $response = $this->sendGet('/api/gateway/topologies?limit=2&offset=1&order_by=name-');

        self::assertEquals(200, $response->status);
        self::assertEquals(1, $response->content->offset);
        self::assertEquals(2, $response->content->limit);
        self::assertEquals(2, $response->content->count);
        self::assertEquals(4, $response->content->total);

        self::assertTopology($topologies[2], $response->content->items[0]);
        self::assertTopology($topologies[1], $response->content->items[1]);
    }

    /**
     * @covers TopologyController::getTopologyAction()
     */
    public function testGetTopology(): void
    {
        $topology = $this->createTopologies()[0];

        $response = $this->sendGet(sprintf('/api/gateway/topologies/%s', $topology->getId()));

        self::assertEquals(200, $response->status);
        self::assertTopology($topology, $response->content);
    }

    /**
     * @covers TopologyController::getTopologyAction()
     */
    public function testGetTopologyNotFound(): void
    {
        $response = $this->sendGet('/api/gateway/topologies/999');

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $response->content->type);
        self::assertEquals(TopologyException::TOPOLOGY_NOT_FOUND, $response->content->error_code);
    }

    /**
     * @covers TopologyController::createTopologyAction()
     */
    public function testCreateTopology(): void
    {
        $response = $this->sendPost('/api/gateway/topologies', [
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

        $response = $this->sendPut(sprintf('/api/gateway/topologies/%s', $topology->getId()), [
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
        $response = $this->sendPut(sprintf('/api/gateway/topologies/999'), [
            'name'    => 'Topology 2',
            'descr'   => 'Topology 2',
            'enabled' => TRUE,
        ]);

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $response->content->type);
        self::assertEquals(TopologyException::TOPOLOGY_NOT_FOUND, $response->content->error_code);
    }

    /**
     * @covers TopologyController::getTopologySchema()
     */
    public function testGetTopologySchema(): void
    {
        $topology = $this->createTopologies()[0];

        $this->client->request(
            'GET',
            sprintf('/api/gateway/topologies/%s/schema.bpmn', $topology->getId())
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
     * @covers TopologyController::getTopologySchema()
     */
    public function testGetTopologySchemaNotFound(): void
    {
        $this->client->request(
            'GET',
            '/api/gateway/topologies/999/schema.bpmn'
        );

        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode($response->getContent()),
        ];

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $response->content->type);
        self::assertEquals(TopologyException::TOPOLOGY_NOT_FOUND, $response->content->error_code);
    }

    /**
     * @covers TopologyController::saveTopologySchema()
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
            sprintf('/api/gateway/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT'       => 'application/xml',
            ],
            $this->getBpmn()
        );

        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode($response->getContent()),
        ];

        self::assertEquals(200, $response->status);
    }

    /**
     * @covers TopologyController::saveTopologySchema()
     */
    public function testSaveTopologySchemaNotFound(): void
    {
        $this->client->request(
            'PUT',
            '/api/gateway/topologies/999/schema.bpmn',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT'       => 'application/xml',
            ],
            $this->getBpmn()
        );

        $response = $this->client->getResponse();
        $response = (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode($response->getContent()),
        ];

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $response->content->type);
        self::assertEquals(TopologyException::TOPOLOGY_NOT_FOUND, $response->content->error_code);
    }

    /**
     * @covers TopologyController::saveTopologySchema()
     * @covers TopologyController::getTopologySchema()
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
            sprintf('/api/gateway/topologies/%s/schema.bpmn', $topology->getId()),
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
            sprintf('/api/gateway/topologies/%s/schema.bpmn', $topology->getId())
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
     */
    private function getBpmnArray(): array
    {
        return Json::decode(file_get_contents(sprintf('%s/data/schema.json', __DIR__)), Json::FORCE_ARRAY);
    }

}