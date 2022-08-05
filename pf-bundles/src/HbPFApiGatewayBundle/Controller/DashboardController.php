<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DashboardController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class DashboardController extends AbstractController
{

    /**
     * @Route("/dashboards/default", methods={"GET"})
     *
     * @return Response
     */
    public function getDashboardAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\DashboardController::getDashboardAction',
        );
    }

}
