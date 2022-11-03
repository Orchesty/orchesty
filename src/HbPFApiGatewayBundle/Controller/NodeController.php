<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/topologies/{id}/nodes", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodesAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getNodesAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/nodes/{id}", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodeAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getNodeAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/nodes/{id}", methods={"PATCH", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function updateNodeAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::updateNodeAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/nodes/list/name", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function listNodesNamesAction(): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getNodes());
    }

}
