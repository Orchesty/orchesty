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
     * @Route("/nodes/{type}/list_nodes", requirements={"type"="connector|custom_node|user"}, methods={"GET", "OPTIONS"})
     *
     * @param string $type
     *
     * @return Response
     */
    public function listOfNodesAction(string $type): Response
    {
        switch ($type) {
            case 'connector':
                return $this->forward(
                    'Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::listOfConnectorsAction',
                );
            case 'custom_node':
                return $this->forward(
                    'Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::listOfCustomNodesAction',
                );
            case 'user':
                return new JsonResponse(ServiceLocator::USER_TASK_LIST);
        }

        return new JsonResponse();
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
