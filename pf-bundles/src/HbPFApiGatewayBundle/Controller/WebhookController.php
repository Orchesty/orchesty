<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class WebhookController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class WebhookController extends AbstractFOSRestController
{

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
        return $this->forward('HbPFApplicationBundle:Webhook:subscribeWebhooks', [
            'request' => $request,
            'key'     => $key,
            'user'    => $user,
        ]);
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
        return $this->forward('HbPFApplicationBundle:Webhook:unsubscribeWebhooks', [
            'request' => $request,
            'key'     => $key,
            'user'    => $user,
        ]);
    }

}
