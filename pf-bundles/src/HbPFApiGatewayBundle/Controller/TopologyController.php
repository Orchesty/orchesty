<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TopologyController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class TopologyController extends AbstractController
{

    use ControllerTrait;

    /**
     * @Route("/topologies", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getTopologiesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologiesAction',
            ['query' => $request->query]
        );
    }

    /**
     * @Route("/topologies/cron", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getCronTopologiesAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getCronTopologiesAction'
        );
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologyAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topologies", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function createTopologyAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::createTopologyAction'
        );
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"PUT", "PATCH", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function updateTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::updateTopologyAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getTopologySchemaAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologySchemaAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"}, methods={"PUT", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function saveTopologySchemaAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topologies/{id}/publish", defaults={}, requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function publishTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::publishTopologyAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topologies/{id}/clone", defaults={}, requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function cloneTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::cloneTopologyAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::deleteTopologyAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topologies/{topologyId}/test", methods={"GET", "OPTIONS"})
     *
     * @param string $topologyId
     *
     * @return Response
     */
    public function testAction(string $topologyId): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::testAction',
            ['topologyId' => $topologyId]
        );
    }

}
