<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class TopologyController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class TopologyController extends AbstractController
{

    use ControllerTrait;

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/topologies', methods: ['GET'])]
    public function getTopologiesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologiesAction',
            ['query' => $request->query],
        );
    }

    /**
     * @return Response
     */
    #[Route('/topologies/cron', methods: ['GET'])]
    public function getCronTopologiesAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getCronTopologiesAction',
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}', requirements: ['id' => '\w+'], methods: ['GET'])]
    public function getTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologyAction',
            ['id' => $id],
        );
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/run', requirements: ['id' => '\w+'], methods: ['POST'])]
    public function runTopologiesAction(Request $request, string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::runTopologiesAction',
            ['request' => $request, 'id' => $id],
        );
    }

    /**
     * @param Request $request
     * @param string  $topologyName
     * @param string  $nodeName
     *
     * @return Response
     */
    #[Route('/topologies/{topologyName}/nodes/{nodeName}/run-by-name', methods: ['POST'])]
    public function runTopologyByNameAction(Request $request, string $topologyName, string $nodeName): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::runTopologyByNameAction',
            ['request' => $request, 'topologyName' => $topologyName, 'nodeName' => $nodeName],
        );
    }

    /**
     * @return Response
     */
    #[Route('/topologies', methods: ['POST'])]
    public function createTopologyAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::createTopologyAction',
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}', requirements: ['id' => '\w+'], methods: ['PUT', 'PATCH'])]
    public function updateTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::updateTopologyAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/{id}/schema.bpmn',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'xml'],
        methods: ['GET'],
    )]
    public function getTopologySchemaAction(string $id): Response
    {
        // TODO RB: Remove this after new UI
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologySchemaAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/{id}/schema.bpmn',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'xml'],
        methods: ['PUT'],
    )]
    public function saveTopologySchemaAction(string $id): Response
    {
        // TODO RB: Remove this after new UI
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/check/{id}/schema.bpmn',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'xml'],
        methods: ['POST'],
    )]
    public function checkTopologySchemaDifferencesAction(string $id): Response
    {
        // TODO RB: Remove this after new UI
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::checkTopologySchemaDifferencesAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/{id}/schema.json',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'json'],
        methods: ['GET'],
    )]
    public function getTopologyJsonSchemaAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologyJsonSchemaAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/{id}/schema.json',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'json'],
        methods: ['PUT'],
    )]
    public function saveTopologyJsonSchemaAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologyJsonSchemaAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/check/{id}/schema.json',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'json'],
        methods: ['POST'],
    )]
    public function checkTopologyJsonSchemaDifferencesAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::checkTopologyJsonSchemaDifferencesAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/publish', requirements: ['id' => '\w+'], methods: ['POST'])]
    public function publishTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::publishTopologyAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/republish', requirements: ['id' => '\w+'], methods: ['POST'])]
    public function republishTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::republishTopologyAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/unpublish', requirements: ['id' => '\w+'], methods: ['POST'])]
    public function unpublishTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::unpublishTopologyAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/clone', requirements: ['id' => '\w+'], methods: ['POST'])]
    public function cloneTopologyAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::cloneTopologyAction',
            ['id' => $id],
        );
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}', requirements: ['id' => '\w+'], methods: ['DELETE'])]
    public function deleteTopologyAction(Request $request, string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::deleteTopologyAction',
            ['request' => $request, 'id' => $id],
        );
    }

    /**
     * @param string $topologyId
     *
     * @return Response
     */
    #[Route('/topologies/{topologyId}/test', methods: ['GET'])]
    public function testAction(string $topologyId): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::testAction',
            ['topologyId' => $topologyId],
        );
    }

}
