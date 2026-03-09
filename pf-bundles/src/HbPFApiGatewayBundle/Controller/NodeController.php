<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class NodeController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class NodeController extends AbstractController
{

    /**
     * NodeController constructor.
     *
     * @param ServiceLocator $locator
     */
    public function __construct(private ServiceLocator $locator)
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/applications/topologies/nodes', methods: ['GET'])]
    public function getTopologiesNodesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getTopologiesNodesAction',
            ['request' => $request],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/nodes', methods: ['GET'])]
    public function getNodesAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getNodesAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/nodes/{id}', methods: ['GET'])]
    public function getNodeAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getNodeAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/nodes/{id}', methods: ['PATCH'])]
    public function updateNodeAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::updateNodeAction',
            ['id' => $id],
        );
    }

    /**
     * @return Response
     */
    #[Route('/nodes/list/name', methods: ['GET'])]
    public function listNodesNamesAction(): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getNodes());
    }

}
