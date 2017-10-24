<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 15:06
 */

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Handler\CMEventsHandler;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CMEventController
 *
 * @package CleverConnectors\AppBundle\Controller
 *
 * @Route(service="cc.cm_events.controller")
 */
class CMEventsController extends FOSRestController
{

    /**
     * @var CMEventsHandler
     */
    private $handler;

    /**
     * CMEventController constructor.
     *
     * @param CMEventsHandler $handler
     */
    public function __construct(CMEventsHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/event/create/{userId}")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return Response
     */
    public function createAction(Request $request, string $userId): Response
    {
        $this->handler->createEvent($request, $userId);

        return new Response([], 200);
    }

    /**
     * @Route("/event/unsubscribe/{userId}")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return Response
     */
    public function unsubscribeAction(Request $request, string $userId): Response
    {
        $this->handler->unsubscribeEvent($request, $userId);

        return new Response([], 200);
    }

    /**
     * @Route("/event/hard_bounce/{userId}")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return Response
     */
    public function HardBounceAction(Request $request, string $userId): Response
    {
        $this->handler->hardBounceEvent($request, $userId);

        return new Response([], 200);
    }

}