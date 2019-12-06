<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesFramework\Configurator\Enum\NodeImplementationEnum;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NodeController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class NodeController extends AbstractFOSRestController
{

    /**
     * @Route("/topologies/{id}/nodes", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodesAction(string $id): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Node:getNodes', ['id' => $id]);
    }

    /**
     * @Route("/nodes/{id}", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodeAction(string $id): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Node:getNode', ['id' => $id]);
    }

    /**
     * @Route("/nodes/{id}", defaults={}, requirements={"id": "\w+"}, methods={"PATCH", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function updateNodeAction(string $id): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Node:updateNode', ['id' => $id]);
    }

    /**
     * @Route("/nodes/{type}/list_nodes", requirements={"type"="connector|custom_node|joiner|mapper|long_running"}, methods={"GET"})
     *
     * @param string $type
     *
     * @return Response
     */
    public function listOfNodesAction(string $type): Response
    {
        switch ($type) {
            case 'connector':
                return $this->forward('HbPFConnectorBundle:Connector:listOfConnectors');
            case 'joiner':
            case 'custom_node':
                return $this->forward('HbPFCustomNodeBundle:CustomNode:listOfCustomNodes');
            case 'long_running':
                return $this->forward('HbPFLongRunningNodeBundle:LongRunningNode:listOfLongRunningNodes');
            case 'mapper':
                return $this->forward('HbPFMapperBundle:Mapper:listOfMappers');
        }

        return new JsonResponse();
    }

    /**
     * @Route("/nodes/list/name", methods={"GET"})
     *
     * @return Response
     */
    public function listNodesNamesAction(): Response
    {
        return new JsonResponse(
            [
                NodeImplementationEnum::PHP => [
                    NodeImplementationEnum::CONNECTOR => $this->getForwardContent(
                        'HbPFConnectorBundle:Connector:listOfConnectors'
                    ),
                    NodeImplementationEnum::CUSTOM    => $this->getForwardContent(
                        'HbPFCustomNodeBundle:CustomNode:listOfCustomNodes'
                    ),
                    NodeImplementationEnum::USER      => $this->getForwardContent(
                        'HbPFLongRunningNodeBundle:LongRunningNode:listOfLongRunningNodes'
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
