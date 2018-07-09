<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class WebhookController
 *
 * @package CleverConnectors\AppBundle\Controller
 */
class WebhookController extends FOSRestController
{

    use ControllerTrait;

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
     * @Route("/webhook/{userId}/{token}/{nodeName}/{topologyName}", methods={"POST"})
     *
     * @param Request $request
     * @param string  $nodeName
     * @param string  $topologyName
     *
     * @return Response
     */
    public function webhookAction(Request $request, string $nodeName, string $topologyName): Response
    {
        $this->handler->runWithRequest($request, $topologyName, $nodeName);

        return $this->getResponse('', 200);
    }

}
