<?php declare(strict_types=1);

namespace Tests\Controller\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\TopologyController;
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

        $this->assertTopology($topologies[2], $response->content->items[0]);
        $this->assertTopology($topologies[1], $response->content->items[1]);
    }

    /**
     * @covers TopologyController::getTopologyAction()
     */
    public function testGetTopology(): void
    {
        /** @var Topology $topology */
        $topology = $this->createTopologies()[0];

        $response = $this->sendGet('/api/gateway/topologies/' . $topology->getId());

        self::assertEquals(200, $response->status);

        $this->assertTopology($topology, $response->content);
    }

    /**
     * @covers TopologyController::updateTopologyAction()
     */
    public function testUpdateTopology(): void
    {
        self::markTestIncomplete();
    }

    /**
     * @covers TopologyController::getTopologySchema()
     */
    public function testGetTopologySchema(): void
    {
        /** @var Topology $topology */
        $topology = $this->createTopologies()[0];

        $response = $this->sendGet('/api/gateway/topologies/' . $topology->getId() . '/schema.bpmn');

        self::assertEquals(200, $response->status);
        self::assertEquals($topology->getBpmn(), $response->content);
    }

    /**
     * @covers TopologyController::saveTopologySchema()
     */
    public function testSaveTopologySchema(): void
    {
        self::markTestIncomplete();
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
     * @return array
     */
    private function createTopologies(int $count = 1): array
    {
        $data = [];
        for ($i = 1; $i <= $count; $i++) {
            $topology = new Topology();
            $topology
                ->setName('name ' . $i)
                ->setDescr('descr ' . $i)
                ->setEnabled(TRUE)
                ->setBpmn($this->getBpmn());

            $this->dm->persist($topology);
            $this->dm->flush();

            $data[] = $topology;
        }

        return $data;
    }

    /**
     * @return string
     */
    private function getBpmn(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
    <bpmn:process id="Process_1" isExecutable="false">
        <bpmn:startEvent id="StartEvent_1" />
        <bpmn:task id="Task_1" />
    </bpmn:process>
    <bpmndi:BPMNDiagram id="BPMNDiagram_1">
        <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
            <bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">
                <dc:Bounds x="173" y="102" width="36" height="36" />
            </bpmndi:BPMNShape>
            <bpmndi:BPMNShape id="Task_1_di" bpmnElement="Task_1">
                <dc:Bounds x="353" y="80" width="100" height="80" />
            </bpmndi:BPMNShape>
        </bpmndi:BPMNPlane>
    </bpmndi:BPMNDiagram>
</bpmn:definitions>';
    }

}