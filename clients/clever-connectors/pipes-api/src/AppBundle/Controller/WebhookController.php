<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WebhookController
 *
 * @package CleverConnectors\AppBundle\Controller
 *
 * @Route(service="cc.controller.webhook")
 */
class WebhookController extends FOSRestController
{

    /**
     * @var StartingPointHandler
     */
    private $handler;

    /**
     * WebhookController constructor.
     *
     * @param StartingPointHandler $handler
     */
    function __construct(StartingPointHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/webhook/{userId}/{token}/{nodeName}/{topologyName}")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $nodeName
     * @param string  $topologyName
     *
     * @return Response
     */
    public function webhookAction(Request $request, string $userId, string $token, string $nodeName, string $topologyName): Response
    {
        $request->headers->set('guid', $userId);
        $request->headers->set('token', $token);

        $this->handler->runWithRequest($request, $topologyName, $nodeName);

        return new Response('res', 200);
    }

}
