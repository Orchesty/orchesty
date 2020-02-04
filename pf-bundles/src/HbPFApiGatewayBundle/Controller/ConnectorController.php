<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class ConnectorController extends AbstractController
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
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processEventAction',
            ['id' => $id]
        );
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
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processActionAction',
            ['id' => $id]
        );
    }

}
