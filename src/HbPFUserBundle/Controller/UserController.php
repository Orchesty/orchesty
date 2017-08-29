<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManagerException;
use Hanaboso\PipesFramework\User\Model\Token\TokenManagerException;
use Hanaboso\PipesFramework\User\Model\User\UserManagerException;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Controller
 *
 * @Route(service="hbpf.user.controller.user")
 */
class UserController extends FOSRestController
{

    /**
     * @var UserHandler
     */
    private $userHandler;

    /**
     * UserController constructor.
     *
     * @param UserHandler $userHandler
     */
    public function __construct(UserHandler $userHandler)
    {
        $this->userHandler = $userHandler;
    }

    /**
     *
     * @Route("/api/user/login")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function loginAction(Request $request): JsonResponse
    {

        try {
            $response = new JsonResponse($this->userHandler->login($request->request->all())->toArray(), 200);
        } catch (SecurityManagerException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     *
     * @Route("/api/user/logout")
     * @Method({"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function logoutAction(): Response
    {
        $this->userHandler->logout();

        return $this->handleView($this->view([], 200));
    }

    /**
     *
     * @Route("/api/user/register")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function registerAction(Request $request): Response
    {
        try {
            $this->userHandler->register($request->request->all());
            $view = $this->view([], 200);
        } catch (UserManagerException $e) {
            $view = $this->view(ControllerUtils::createExceptionData($e), 500);
        }

        return $this->handleView($view);
    }

    /**
     *
     * @Route("/api/user/{token}/activate", requirements={"token": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function activateAction(string $token): Response
    {
        try {
            $this->userHandler->activate($token);
            $view = $this->view([], 200);
        } catch (TokenManagerException $e) {
            $view = $this->view(ControllerUtils::createExceptionData($e), 500);
        }

        return $this->handleView($view);
    }

    /**
     *
     * @Route("/api/user/{token}/set_password", requirements={"token": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
    public function setPasswordAction(Request $request, string $token): Response
    {
        try {
            $this->userHandler->setPassword($token, $request->request->all());
            $view = $this->view([], 200);
        } catch (TokenManagerException $e) {
            $view = $this->view(ControllerUtils::createExceptionData($e), 500);
        }

        return $this->handleView($view);
    }

    /**
     *
     * @Route("/api/user/reset_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function resetPasswordAction(Request $request): Response
    {
        try {
            $this->userHandler->resetPassword($request->request->all());
            $view = $this->view([], 200);
        } catch (UserManagerException $e) {
            $view = $this->view(ControllerUtils::createExceptionData($e), 500);
        }

        return $this->handleView($view);
    }

}