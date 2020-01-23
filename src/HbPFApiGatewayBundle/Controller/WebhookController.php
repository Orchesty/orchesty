<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\WebhookController::subscribeWebhooksAction',
            [
                'request' => $request,
                'key'     => $key,
                'user'    => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\WebhookController::unsubscribeWebhooksAction',
            [
                'request' => $request,
                'key'     => $key,
                'user'    => $user,
            ]
        );
    }

}
