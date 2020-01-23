<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NotificationController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class NotificationController extends AbstractController
{

    /**
     * @Route("/notification_settings", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getSettingsAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::getSettingsAction'
        );
    }

    /**
     * @Route("/notification_settings/events", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getSettingEventsAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::getSettingEventsAction'
        );
    }

    /**
     * @Route("/notification_settings/{id}", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getSettingAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::getSettingAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/notification_settings/{id}", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateSettingsAction(Request $request, string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::updateSettingsAction',
            [
                'request' => $request,
                'id'      => $id,
            ]
        );
    }

}
