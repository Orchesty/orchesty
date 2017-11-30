<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.user")
 */
class UserController extends FOSRestController
{

    /**
     *
     * @Route("/user/login")
     * @Method({"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function loginAction(): Response
    {
        return $this->forward('HbPFUserBundle:User:login');
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
        return $this->forward('HbPFUserBundle:User:logout');
    }

    /**
     *
     * @Route("/user/register")
     * @Method({"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function registerAction(): Response
    {
        return $this->forward('HbPFUserBundle:User:register');
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
        return $this->forward('HbPFUserBundle:User:activate', ['token' => $token]);
    }

    /**
     *
     * @Route("/user/{token}/set_password", requirements={"token": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function setPasswordAction(string $token): Response
    {
        return $this->forward('HbPFUserBundle:User:setPassword', ['token' => $token]);
    }

    /**
     * @Route("/user/change_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function changePasswordAction(): Response
    {
        return $this->forward('HbPFUserBundle:User:changePassword');
    }

    /**
     *
     * @Route("/user/reset_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function resetPasswordAction(): Response
    {
        return $this->forward('HbPFUserBundle:User:resetPassword');
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
        return $this->forward('HbPFUserBundle:User:delete', ['id' => $id]);
    }

}