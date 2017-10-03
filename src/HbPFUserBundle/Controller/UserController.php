<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Acl\Exception\AclException;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManagerException;
use Hanaboso\PipesFramework\User\Model\Token\TokenManagerException;
use Hanaboso\PipesFramework\User\Model\User\UserManagerException;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
     *
     * @Route("/user/login")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function loginAction(Request $request): JsonResponse
    {
        $this->construct();
        try {
            return new JsonResponse($this->userHandler->login($request->request->all())->toArray(), 200, []);
        } catch (SecurityManagerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     *
     * @Route("/user/logout")
     * @Method({"POST", "OPTIONS"})
     *
     * @return JsonResponse
     */
    public function logoutAction(): JsonResponse
    {
        $this->construct();
        try {
            return new JsonResponse($this->userHandler->logout());
        } catch (SecurityManagerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     *
     * @Route("/user/register")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function registerAction(Request $request): JsonResponse
    {
        $this->construct();
        try {
            return new JsonResponse($this->userHandler->register($request->request->all()));
        } catch (UserManagerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     *
     * @Route("/user/{token}/activate", requirements={"token": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    public function activateAction(string $token): JsonResponse
    {
        $this->construct();
        try {
            return new JsonResponse($this->userHandler->activate($token));
        } catch (TokenManagerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     *
     * @Route("/user/{token}/set_password", requirements={"token": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $token
     *
     * @return JsonResponse
     */
    public function setPasswordAction(Request $request, string $token): JsonResponse
    {
        $this->construct();
        try {
            return new JsonResponse($this->userHandler->setPassword($token, $request->request->all()));
        } catch (TokenManagerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user/change_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function changePasswordAction(Request $request): JsonResponse
    {
        $this->construct();
        try {
            return new JsonResponse($this->userHandler->changePassword($request->request->all()));
        } catch (SecurityManagerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     *
     * @Route("/user/reset_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function resetPasswordAction(Request $request): JsonResponse
    {
        $this->construct();
        try {
            return new JsonResponse($this->userHandler->resetPassword($request->request->all()));
        } catch (UserManagerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

    }

    /**
     *
     * @Route("/user/{id}/delete")
     * @Method({"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function deleteAction(string $id): JsonResponse
    {
        $this->construct();
        try {
            return new JsonResponse($this->userHandler->delete($id)->toArray(), 200);
        } catch (AclException | UserManagerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     *
     */
    private function construct(): void
    {
        if (!$this->userHandler) {
            $this->userHandler = $this->container->get('hbpf.user.handler.user');
        }
    }

}