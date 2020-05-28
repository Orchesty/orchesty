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
     * @var ServiceLocator
     */
    private ServiceLocator $locator;

    /**
     * NodeController constructor.
     *
     * @param ServiceLocator $locator
     */
    public function __construct(ServiceLocator $locator)
    {
        $this->locator = $locator;
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
            ['id' => $id]
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
            ['id' => $id]
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
            ['id' => $id]
        );
    }

    /**
     * @Route("/nodes/{type}/list_nodes", requirements={"type"="connector|custom_node|joiner|mapper|long_running"}, methods={"GET", "OPTIONS"})
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
                    'Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::listOfConnectorsAction'
                );
            case 'joiner':
            case 'custom_node':
                return $this->forward(
                    'Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::listOfCustomNodesAction'
                );
            case 'long_running':
                return $this->forward(
                    'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::listOfLongRunningNodesAction'
                );
            case 'mapper':
                return $this->forward(
                    'Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::listOfMappersAction'
                );
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
