<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class NotificationController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class NotificationController extends AbstractController
{

    /**
     * @return Response
     */
    #[Route('/notifications/subscriptions', methods: ['GET'])]
    public function listSubscriptionsAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Controller\NotificationController::listSubscriptionsAction',
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/notifications/subscriptions', methods: ['PUT'])]
    public function upsertSubscriptionAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Controller\NotificationController::upsertSubscriptionAction',
            ['request' => $request],
        );
    }

}
