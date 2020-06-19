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
     * @var ServiceLocator
     */
    private ServiceLocator $locator;

    /**
     * WebhookController constructor.
     *
     * @param ServiceLocator $locator
     */
    public function __construct(ServiceLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @Route("/webhook/applications/{key}/users/{user}/subscribe", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function subscribeWebhooksAction(Request $request, string $key, string $user): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->subscribeWebhook($key, $user, $request->request->all()));
    }

    /**
     * @Route("/webhook/applications/{key}/users/{user}/unsubscribe", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function unsubscribeWebhooksAction(Request $request, string $key, string $user): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->unSubscribeWebhook($key, $user, $request->request->all()));
    }

}
