<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class WebhookController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class WebhookController extends AbstractController
{

    // phpcs:disable SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing.IncorrectLinesCountBetweenAttributeAndTarget

    /**
     * WebhookController constructor.
     *
     * @param ServiceLocator $locator
     */
    public function __construct(private readonly ServiceLocator $locator)
    {
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $sdk
     *
     * @return Response
     */
    #[Route('/webhook/applications/{key}/subscribe', methods: ['POST', 'OPTIONS'])]
    public function subscribeWebhooksAction(Request $request, string $key, #[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->subscribeWebhook($key, ApplicationController::SYSTEM_USER, $sdk, $request->request->all()),
        );
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $sdk
     *
     * @return Response
     */
    #[Route('/webhook/applications/{key}/unsubscribe', methods: ['POST', 'OPTIONS'])]
    public function unsubscribeWebhooksAction(Request $request, string $key, #[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->unSubscribeWebhook(
                $key,
                ApplicationController::SYSTEM_USER,
                $sdk,
                $request->request->all(),
            ),
        );
    }

}
