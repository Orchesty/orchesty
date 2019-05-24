<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NotificationController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class NotificationController extends AbstractFOSRestController
{

    /**
     * @Route("/notification_settings", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getSettingsAction(): Response
    {
        return $this->forward('HbPFNotificationBundle:Notification:getSettings');
    }

    /**
     * @Route("/notification_settings/events", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getSettingEventsAction(): Response
    {
        return $this->forward('HbPFNotificationBundle:Notification:getSettingEvents');
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
        return $this->forward('HbPFNotificationBundle:Notification:getSetting', ['id' => $id]);
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
        return $this->forward('HbPFNotificationBundle:Notification:updateSettings', [
            'request' => $request,
            'id'      => $id,
        ]);
    }

}
