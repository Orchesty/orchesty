<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NotificationController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.notification")
 */
class NotificationController extends FOSRestController
{

    /**
     * @Route("/notification_settings")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getSettingsAction(Request $request): Response
    {
        return $this->forward('HbPFNotificationBundle:Notification:getSettings');
    }

    /**
     * @Route("/notification_settings")
     * @Method({"PUT", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function updateSettingsAction(Request $request): Response
    {
        return $this->forward('HbPFNotificationBundle:Notification:updateSettings');
    }

}