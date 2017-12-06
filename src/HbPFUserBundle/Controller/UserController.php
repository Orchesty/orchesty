<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Acl\Exception\AclException;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManagerException;
use Hanaboso\PipesFramework\User\Model\Token\TokenManagerException;
use Hanaboso\PipesFramework\User\Model\User\UserManagerException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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

    use ControllerTrait;

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
     * @return Response
     */
    public function loginAction(Request $request): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->userHandler->login($request->request->all())->toArray());
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     *
     * @Route("/user/logout")
     * @Method({"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function logoutAction(): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->userHandler->logout());
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     *
     * @Route("/user/register")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function registerAction(Request $request): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->userHandler->register($request->request->all()));
        } catch (UserManagerException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     *
     * @Route("/user/{token}/activate", requirements={"token": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function activateAction(string $token): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->userHandler->activate($token));
        } catch (TokenManagerException $e) {
            return $this->getErrorResponse($e);
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
     * @return Response
     */
    public function setPasswordAction(Request $request, string $token): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->userHandler->setPassword($token, $request->request->all()));
        } catch (TokenManagerException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/user/change_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function changePasswordAction(Request $request): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->userHandler->changePassword($request->request->all()));
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     *
     * @Route("/user/reset_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function resetPasswordAction(Request $request): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->userHandler->resetPassword($request->request->all()));
        } catch (UserManagerException $e) {
            return $this->getErrorResponse($e);
        }

    }

    /**
     *
     * @Route("/user/{id}/delete")
     * @Method({"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction(string $id): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->userHandler->delete($id)->toArray());
        } catch (AclException | UserManagerException $e) {
            return $this->getErrorResponse($e);
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