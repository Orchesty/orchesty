<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class WebhookController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class WebhookController extends AbstractController
{

    /**
     * WebhookController constructor.
     *
     * @param ServiceLocator $locator
     */
    public function __construct(private readonly ServiceLocator $locator)
    {
    }

    /**
     * @Route("/webhook/applications/{key}/subscribe", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function subscribeWebhooksAction(Request $request, string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->subscribeWebhook($key, ApplicationController::SYSTEM_USER, $request->request->all()),
        );
    }

    /**
     * @Route("/webhook/applications/{key}/unsubscribe", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function unsubscribeWebhooksAction(Request $request, string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->unSubscribeWebhook($key, ApplicationController::SYSTEM_USER, $request->request->all()),
        );
    }

}
