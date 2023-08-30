<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Controller;

use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Handler\NotificationSettingsHandler;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class NotificationSettingsController
 *
 * @package Hanaboso\NotificationSender\Controller
 *
 * @Route("/notifications/settings")
 */
final class NotificationSettingsController
{

    use ControllerTrait;

    /**
     * NotificationSettingsController constructor.
     *
     * @param NotificationSettingsHandler $handler
     */
    public function __construct(private NotificationSettingsHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @Route("", methods={"GET", "OPTIONS"})
     * @Route("/", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function listSettingsAction(): Response
    {
        try {
            return $this->getResponse($this->handler->listSettings());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/{id}", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getSettingsAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->getSettings($id));
        } catch (NotificationException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/{id}", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function saveSettingsAction(Request $request, string $id): Response
    {
        try {
            $res = $this->handler->saveSettings($id, $request->request->all());
            if ($res[NotificationSettings::STATUS] === FALSE && empty($res[NotificationSettings::STATUS_MESSAGE])) {
                return $this->getResponse($res);
            }

            return $this->getResponse($res, 400);
        } catch (NotificationException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND, $request->headers->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

}
