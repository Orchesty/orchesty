<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class ConnectorController extends FOSRestController
{

    /**
     * @Route("/connector/{id}/webhook", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"POST", "OPTIONS"})
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
     * @Route("/connector/{id}/action", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"POST", "OPTIONS"})
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