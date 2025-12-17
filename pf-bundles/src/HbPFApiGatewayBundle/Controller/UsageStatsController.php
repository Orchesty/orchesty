<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class UsageStatsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class UsageStatsController extends AbstractController
{

    use ControllerTrait;

    /**
     * @return Response
     */
    #[Route('/usage-stats/emit-event', methods: ['POST'])]
    public function loginAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUsageStatsBundle\Controller\UsageStatsController::emitEventAction',
        );
    }

}
