<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller;

use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class WebhookController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller
 */
final class WebhookController
{

    use ControllerTrait;

    /**
     * WebhookController constructor.
     *
     * @param WebhookHandler $webhookHandler
     */
    public function __construct(private WebhookHandler $webhookHandler)
    {
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
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
