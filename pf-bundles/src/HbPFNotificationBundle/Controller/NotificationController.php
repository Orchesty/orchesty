<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFNotificationBundle\Controller;

use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class NotificationController
 *
 * @package Hanaboso\PipesFramework\HbPFNotificationBundle\Controller
 */
final class NotificationController
{

    use ControllerTrait;

    /**
     * @var NotificationHandler
     */
    private NotificationHandler $notificationHandler;

    /**
     * NotificationController constructor.
     *
     * @param NotificationHandler $notificationHandler
     */
    public function __construct(NotificationHandler $notificationHandler)
    {
        $this->notificationHandler = $notificationHandler;
        $this->logger              = new NullLogger();
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
