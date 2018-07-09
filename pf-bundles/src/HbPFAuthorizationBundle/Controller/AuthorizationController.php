<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler\AuthorizationHandler;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class AuthorizationController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 */
class AuthorizationController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var AuthorizationHandler
     */
    private $authorizationHandler;

    /**
     * AuthorizationController constructor.
     *
     * @param AuthorizationHandler $authorizationHandler
     */
    public function __construct(AuthorizationHandler $authorizationHandler)
    {
        $this->authorizationHandler = $authorizationHandler;
    }

    /**
     * @Route("/authorizations/{authorizationId}/authorize", methods={"GET", "POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function authorizationAction(Request $request, string $authorizationId): Response
    {
        try {
            $this->authorizationHandler->authorize($authorizationId);

            return new RedirectResponse($request->request->get('redirect_url'));
        } catch (AuthorizationException | InvalidArgumentException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/authorizations/{authorizationId}/settings", methods={"GET", "OPTIONS"})
     *
     * @param string $authorizationId
     *
     * @return Response
     */
    public function getSettingsAction(string $authorizationId): Response
    {
        try {
            return $this->getResponse($this->authorizationHandler->getSettings($authorizationId));
        } catch (AuthorizationException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/authorizations/{authorizationId}/save_settings", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function saveSettingsAction(Request $request, string $authorizationId): Response
    {
        try {
            $this->authorizationHandler->saveSettings($request->request->all(), $authorizationId);

            return $this->getResponse([]);
        } catch (AuthorizationException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/authorizations/{authorizationId}/save_token", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function saveTokenAction(Request $request, string $authorizationId): Response
    {
        try {
            $this->authorizationHandler->saveToken($request->request->all(), $authorizationId);

            return new RedirectResponse($this->container->getParameter('frontend_host') . '/close-me.html');
        } catch (AuthorizationException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/authorizations", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getAuthorizationsAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->authorizationHandler->getAuthInfo($request->getSchemeAndHttpHost()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
