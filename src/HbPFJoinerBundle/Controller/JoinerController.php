<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFJoinerBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Handler\JoinerHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class JoinerController
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Controller
 */
class JoinerController extends AbstractFOSRestController
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

    /**
     * @Route("/joiner/list", methods={"GET"})
     *
     * @return Response
     */
    public function listOfJoinersAction(): Response
    {
        try {
            $data = $this->joinerHandler->getJoiners();

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {

            return $this->getErrorResponse($e, 500);
        }
    }

}
