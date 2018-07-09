<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFNotificationBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class NotificationController
 *
 * @package Hanaboso\PipesFramework\HbPFNotificationBundle\Controller
 */
class NotificationController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var NotificationHandler
     */
    private $notificationHandler;

    /**
     * NotificationController constructor.
     *
     * @param NotificationHandler $notificationHandler
     */
    public function __construct(NotificationHandler $notificationHandler)
    {
        $this->notificationHandler = $notificationHandler;
    }

    /**
     * @Route("/notification_settings", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getSettingsAction(): Response
    {
        try {
            return $this->getResponse($this->notificationHandler->getSettings());
        } catch (NotificationException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/notification_settings", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function updateSettingsAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->notificationHandler->updateSettings($request->request->all()));
        } catch (NotificationException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}