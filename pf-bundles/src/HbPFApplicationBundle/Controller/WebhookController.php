<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Handler\WebhookHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class WebhookController
 *
 * @package Hanaboso\PipesFramework\HbPFApplicationBundle\Controller
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
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function subscribeWebhooksAction(string $key, string $user): Response
    {
        try {
            $this->webhookHandler->subscribeWebhooks($key, $user);

            return $this->getResponse([]);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

    /**
     * @Route("/webhook/applications/{key}/users/{user}/unsubscribe", methods={"POST"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function unsubscribeWebhooksAction(string $key, string $user): Response
    {
        try {
            $this->webhookHandler->unsubscribeWebhooks($key, $user);

            return $this->getResponse([]);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

}