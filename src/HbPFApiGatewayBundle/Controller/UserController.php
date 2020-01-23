<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class UserController extends AbstractController
{

    /**
     * @Route("/user/login", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function loginAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::loginAction');
    }

    /**
     * @Route("/user/logout", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function logoutAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::logoutAction');
    }

    /**
     * @Route("/user/register", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function registerAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::registerAction');
    }

    /**
     * @Route("/user/{token}/activate", requirements={"token": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function activateAction(string $token): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::activateAction', ['token' => $token]);
    }

    /**
     * @Route("/user/{token}/set_password", requirements={"token": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function setPasswordAction(string $token): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::setPasswordAction', ['token' => $token]);
    }

    /**
     * @Route("/user/change_password", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function changePasswordAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::changePasswordAction');
    }

    /**
     * @Route("/user/reset_password", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function resetPasswordAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::resetPasswordAction');
    }

    /**
     * @Route("/user/{id}/delete", methods={"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction(string $id): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::deleteAction', ['id' => $id]);
    }

}
