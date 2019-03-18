<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
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
     * @Route("/notification_settings", methods={"PUT", "OPTIONS"})
     *
     * @return Response
     */
    public function updateSettingsAction(): Response
    {
        return $this->forward('HbPFNotificationBundle:Notification:updateSettings');
    }

}
