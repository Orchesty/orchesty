<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:53 PM
 */

namespace Hanaboso\PipesFramework\HbPFJoinerBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Handler\JoinerHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class JoinerController
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Controller
 *
 * @Route(service="hbpf.controller.joiner")
 */
class JoinerController extends FOSRestController
{

    /**
     * @var JoinerHandler
     */
    private $handler;

    /**
     * JoinerController constructor.
     *
     * @param JoinerHandler $handler
     */
    function __construct(JoinerHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/api/joiner/{joinerId}/join", defaults={}, requirements={"joinerId": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $joinerId
     *
     * @return JsonResponse
     */
    public function sendAction(Request $request, string $joinerId): JsonResponse
    {
        try {
            $data     = $this->handler->processJoiner($joinerId, $request->request->all());
            $response = new JsonResponse($data, 200);
        } catch (JoinerException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     * @Route("/api/joiner/{joinerId}/join/test", defaults={}, requirements={"joinerId": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $joinerId
     *
     * @return JsonResponse
     */
    public function sendTestAction(Request $request, string $joinerId): JsonResponse
    {
        try {
            $this->handler->processJoinerTest($joinerId, $request->request->all());
            $response = new JsonResponse([], 200);
        } catch (JoinerException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

}