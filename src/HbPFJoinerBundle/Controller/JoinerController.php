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
     *
     * @param Request $request
     * @param string  $joinerId
     *
     * @return Response
     */
    public function sendAction(Request $request, string $joinerId): Response
    {
        $res = $this->handler->processJoiner($joinerId, $request->request->all());

        return $this->handleView($this->view($res));
    }

    /**
     * @Route("/api/joiner/{joinerId}/join/test", defaults={}, requirements={"joinerId": "\w+"})
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