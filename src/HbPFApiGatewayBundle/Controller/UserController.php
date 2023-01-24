<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Error;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class UserController extends AbstractController
{

    use ControllerTrait;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @Route("/user/login", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function loginAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loginUserAction');
    }

    /**
     * @Route("/user/logout", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function logoutAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::logoutAction');
    }

    /**
     * @Route("/user/check_logged", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function loggedUserAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loggedUserAction');
    }

    /**
     * @Route("/user/register", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function registerAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::registerAction');
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
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::activateAction',
            ['token' => $token],
        );
    }

    /**
     * @Route("/user/{token}/verify", requirements={"token": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function verifyAction(string $token): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::verifyAction',
            ['token' => $token],
        );
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
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::setPasswordAction',
            ['token' => $token],
        );
    }

    /**
     * @Route("/user/change_password", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function changePasswordAction(Request $request): Response
    {
        if($request->request->get('old_password') !== NULL) {
            return $this->forward(
                'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::changePasswordAction',
            );
        }

        return $this->getErrorResponse(new Error('Missing old password'), 403);
    }

    /**
     * @Route("/user/reset_password", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function resetPasswordAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::resetPasswordAction');
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
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::deleteAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/user/list", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getAllUsersAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getAllUsersAction',
            [],
            $request->query->all(),
        );
    }

    /**
     * @Route("/user/{id}/saveSettings", methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function saveUserSettingsAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::saveUserSettingsAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/user/{id}", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getUserAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getUserAction',
            ['id' => $id],
        );
    }

}
