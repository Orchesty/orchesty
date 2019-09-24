<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Handler\NotificationSettingsHandler;
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
final class NotificationSettingsController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @var NotificationSettingsHandler
     */
    private $handler;

    /**
     * NotificationSettingsController constructor.
     *
     * @param NotificationSettingsHandler $handler
     */
    public function __construct(NotificationSettingsHandler $handler)
    {
        $this->handler = $handler;
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
            return $this->getResponse($this->handler->saveSettings($id, $request->request->all()));
        } catch (NotificationException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND, $request->headers->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

}
