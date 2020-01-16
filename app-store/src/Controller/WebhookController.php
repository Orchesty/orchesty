<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\HbPFAppStore\Handler\WebhookHandler;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class WebhookController
 *
 * @package Hanaboso\HbPFAppStore\Controller
 */
class WebhookController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @var WebhookHandler
     */
    private $webhookHandler;

    /**
     * WebhookController constructor.
     *
     * @param WebhookHandler $webhookHandler
     */
    public function __construct(WebhookHandler $webhookHandler)
    {
        $this->webhookHandler = $webhookHandler;
    }

    /**
     * @Route("/webhook/applications/{key}/users/{user}/subscribe", methods={"POST"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function subscribeWebhooksAction(Request $request, string $key, string $user): Response
    {
        try {
            $this->webhookHandler->subscribeWebhooks($key, $user, $request->request->all());

            return $this->getResponse([]);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/webhook/applications/{key}/users/{user}/unsubscribe", methods={"POST"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function unsubscribeWebhooksAction(Request $request, string $key, string $user): Response
    {
        try {
            $this->webhookHandler->unsubscribeWebhooks($key, $user, $request->request->all());

            return $this->getResponse([]);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

}
