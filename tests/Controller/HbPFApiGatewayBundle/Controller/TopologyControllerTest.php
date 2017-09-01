<?php declare(strict_types=1);

namespace Tests\Controller\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\TopologyController;
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
        self::markTestIncomplete();
    }

    /**
     * @covers TopologyController::getTopologyAction()
     */
    public function testGetTopology(): void
    {
        $bpmn = '<?xml version="1.0" encoding="UTF-8"?>
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

        $nodes = '[
        {
          "id": "node_1",                    
          "faucet": {
              "type": "amq",
              "config": {}
          },                 
          "worker": {
            "type": "pipes_rest",
            "config": {
              "service": "magento_parser"
            }
          },
          "next": [
            {
              "node": "node_2",
              "settings": {}
            }
          ],
          "drain": {
              "type": "fs",
              "resequencer": true,
              "config": {}
          },
          "opts": 2,
          "view": {
              "type": "parser"
          }
        }
    ]';

        $topology = new Topology();
        $topology
            ->setName('name')
            ->setDescr('descr')
            ->setStatus(true)
            ->setBpmn($bpmn)
            ->setNodes($nodes);

        $this->dm->persist($topology);
        $this->dm->flush();

        $response = $this->sendGet('/api/gateway/topologies/' . $topology->getId());

        self::assertEquals(200, $response->status);
        self::assertEquals($topology->getId(), $response->content->_id);
        self::assertEquals($topology->getName(), $response->content->name);
        self::assertEquals($topology->getDescr(), $response->content->descr);
        self::assertEquals($topology->getStatus(), $response->content->status);
        self::assertEquals($topology->getNodes(), $response->content->nodes);
    }

    /**
     * @covers TopologyController::updateTopologyAction()
     */
    public function testUpdateTopology(): void
    {
        self::markTestIncomplete();
    }

    /**
     * @covers TopologyController::getTopologyScheme()
     */
    public function testGetTopologyScheme(): void
    {
        self::markTestIncomplete();
    }

    /**
     * @covers TopologyController::uploadTopologyScheme()
     */
    public function testUploadTopologyScheme(): void
    {
        self::markTestIncomplete();
    }

}