<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 15:06
 */

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\CMEventsHandler;
use Exception;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CMEventController
 *
 * @package CleverConnectors\AppBundle\Controller
 *
 * @Route(service="cc.events.controller")
 */
class CMEventsController extends FOSRestController
{

    use ControllerTrait;

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
     * @Route("/event/user/{userId}/create")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return Response
     */
    public function createAction(Request $request, string $userId): Response
    {
        try {
            $this->handler->createEvent($request, $userId);
        } catch (CleverConnectorsException $e) {
            return $this->processException($e);
        }

        return $this->getResponse('');
    }

    /**
     * @Route("/event/user/{userId}/unsubscribe")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return Response
     */
    public function unsubscribeAction(Request $request, string $userId): Response
    {
        try {
            $this->handler->unsubscribeEvent($request, $userId);
        } catch (CleverConnectorsException $e) {
            return $this->processException($e);
        }

        return $this->getResponse('');
    }

    /**
     * @Route("/event/user/{userId}/hard_bounce")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return Response
     */
    public function hardBounceAction(Request $request, string $userId): Response
    {
        try {
            $this->handler->hardBounceEvent($request, $userId);
        } catch (CleverConnectorsException $e) {
            return $this->processException($e);
        }

        return $this->getResponse('');
    }

    /**
     * @Route("/event/user/{userId}/subscribe")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return Response
     */
    public function subscribeAction(Request $request, string $userId): Response
    {
        try {
            $this->handler->subscribeEvent($request, $userId);
        } catch (CleverConnectorsException $e) {
            return $this->processException($e);
        }

        return $this->getResponse('');
    }

    /**
     * @param Exception $e
     *
     * @return Response
     */
    private function processException(Exception $e): Response
    {
        $code = 500;

        $className = get_class($e);

        if ($className == CleverConnectorsException::class) {
            if ($e->getCode() == CleverConnectorsException::INVALID_ENUM_VALUE) {
                $code = 400;
            }
            if ($e->getCode() == CleverConnectorsException::TOPOLOGY_NOT_FOUND) {
                $code = 404;
            }
        }

        return $this->getErrorResponse($e, $code);
    }

}