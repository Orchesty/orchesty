<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:53 PM
 */

namespace Hanaboso\PipesFramework\HbPFJoinerBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Handler\JoinerHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class JoinerController
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Controller
 */
class JoinerController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var JoinerHandler
     */
    private $joinerHandler;

    /**
     * JoinerController constructor.
     *
     * @param JoinerHandler $joinerHandler
     */
    public function __construct(JoinerHandler $joinerHandler)
    {
        $this->joinerHandler = $joinerHandler;
    }

    /**
     * @Route("/joiner/{joinerId}/join", defaults={}, requirements={"joinerId": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $joinerId
     *
     * @return Response
     */
    public function sendAction(Request $request, string $joinerId): Response
    {
        try {
            $data = $this->joinerHandler->processJoiner($joinerId, $request->request->all());

            return $this->getResponse($data);
        } catch (JoinerException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/joiner/{joinerId}/join/test", defaults={}, requirements={"joinerId": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $joinerId
     *
     * @return Response
     */
    public function sendTestAction(Request $request, string $joinerId): Response
    {
        try {
            $this->joinerHandler->processJoinerTest($joinerId, $request->request->all());

            return $this->getResponse([]);
        } catch (JoinerException $e) {
            return $this->getErrorResponse($e);
        }
    }

}