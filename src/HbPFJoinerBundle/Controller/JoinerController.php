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
use Hanaboso\PipesFramework\HbPFJoinerBundle\Handler\JoinerHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JoinerController
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Controller
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
        $data = $this->handler->processJoiner($joinerId, $request->request->all());

        return new JsonResponse($data, 200);
    }

    /**
     * @Route("/api/joiner/{joinerId}/join/test", defaults={}, requirements={"joinerId": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $joinerId
     *
     * @return Response
     */
    public function sendTestAction(Request $request, string $joinerId): Response
    {
        $this->handler->processJoinerTest($joinerId, $request->request->all());

        return $this->handleView($this->view());
    }

}