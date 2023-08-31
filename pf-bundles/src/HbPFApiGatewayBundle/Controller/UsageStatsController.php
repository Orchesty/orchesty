<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UsageStatsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class UsageStatsController extends AbstractController
{

    use ControllerTrait;

    /**
     * @Route("/usage-stats/emit-event", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function loginAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUsageStatsBundle\Controller\UsageStatsController::emitEventAction',
        );
    }

}
