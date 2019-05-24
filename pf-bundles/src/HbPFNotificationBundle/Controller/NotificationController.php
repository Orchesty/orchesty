<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFNotificationBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
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
class NotificationController extends AbstractFOSRestController
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
     * @Route("/notification_settings/events", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getSettingEventsAction(): Response
    {
        return $this->getResponse(NotificationEventEnum::getChoices());
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
        try {
            return $this->getResponse($this->notificationHandler->getSetting($id));
        } catch (NotificationException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
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
        try {
            return $this->getResponse($this->notificationHandler->updateSettings($id, $request->request->all()));
        } catch (NotificationException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
