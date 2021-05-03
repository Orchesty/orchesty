<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller;

use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class JoinerController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller
 */
final class JoinerController
{

    use ControllerTrait;

    /**
     * JoinerController constructor.
     *
     * @param JoinerHandler $joinerHandler
     */
    public function __construct(private JoinerHandler $joinerHandler)
    {
    }

    /**
     * @Route("/joiner/{id}/join", defaults={}, requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function sendAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->joinerHandler->processJoiner($id, $request->request->all()));
        } catch (JoinerException $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/joiner/{id}/join/test", defaults={}, requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function sendTestAction(Request $request, string $id): Response
    {
        try {
            $this->joinerHandler->processJoinerTest($id, $request->request->all());

            return $this->getResponse([]);
        } catch (JoinerException $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
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
            return $this->getResponse($this->joinerHandler->getJoiners());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

}
