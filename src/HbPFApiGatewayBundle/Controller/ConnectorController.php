<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.connector")
 */
class ConnectorController extends FOSRestController
{

    /**
     * @Route("/connector/{id}/webhook", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function processEvent(string $id): JsonResponse
    {
        $data = $this->forward('HbPFConnectorBundle:Connector:processEvent', ['id' => $id]);

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

    /**
     * @Route("/connector/{id}/action", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function processAction(string $id): JsonResponse
    {
        $data = $this->forward('HbPFConnectorBundle:Connector:processAction', ['id' => $id]);

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

}