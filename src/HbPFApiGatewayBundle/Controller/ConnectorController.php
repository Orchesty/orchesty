<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class ConnectorController extends AbstractFOSRestController
{

    /**
     * @Route("/connector/{id}/webhook", defaults={}, requirements={"id": "[\w-]+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function processEvent(string $id): Response
    {
        $data = $this->forward('HbPFConnectorBundle:Connector:processEvent', ['id' => $id]);

        return new Response($data->getContent(), $data->getStatusCode(), $data->headers->all());
    }

    /**
     * @Route("/connector/{id}/action", defaults={}, requirements={"id": "[\w-]+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function processAction(string $id): Response
    {
        $data = $this->forward('HbPFConnectorBundle:Connector:processAction', ['id' => $id]);

        return new Response($data->getContent(), $data->getStatusCode(), $data->headers->all());
    }

}
