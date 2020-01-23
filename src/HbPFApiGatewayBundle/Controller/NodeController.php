<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\Configurator\Enum\NodeImplementationEnum;
use Hanaboso\Utils\String\Json;
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
        return new JsonResponse(
            [
                NodeImplementationEnum::PHP => [
                    NodeImplementationEnum::CONNECTOR => $this->getForwardContent(
                        'Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::listOfConnectorsAction'
                    ),
                    NodeImplementationEnum::CUSTOM    => $this->getForwardContent(
                        'Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::listOfCustomNodesAction'
                    ),
                    NodeImplementationEnum::USER      => $this->getForwardContent(
                        'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::listOfLongRunningNodesAction'
                    ),
                ],
            ]
        );
    }

    /**
     * @param string $path
     *
     * @return mixed[]
     */
    private function getForwardContent(string $path): array
    {
        return Json::decode((string) $this->forward($path)->getContent());
    }

}
