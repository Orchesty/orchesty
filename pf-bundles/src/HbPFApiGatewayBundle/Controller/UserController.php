<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Error;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
     * @return Response
     */
    #[Route('/user/login', methods: ['POST'])]
    public function loginAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loginUserAction');
    }

    /**
     * @return Response
     */
    #[Route('/user/logout', methods: ['POST'])]
    public function logoutAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::logoutAction');
    }

    /**
     * @return Response
     */
    #[Route('/user/check_logged', methods: ['GET'])]
    public function loggedUserAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loggedUserAction');
    }

    /**
     * @return Response
     */
    #[Route('/user/exists', methods: ['GET'])]
    public function userExistsAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::userExistsAction');
    }

    /**
     * @return Response
     */
    #[Route('/user/setup', methods: ['POST'])]
    public function setupAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::setupAction');
    }

    /**
     * @return Response
     */
    #[Route('/user/register', methods: ['POST'])]
    public function registerAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::registerAction');
    }

    /**
     * @param string $token
     *
     * @return Response
     */
    #[Route('/user/{token}/activate', requirements: ['token' => '\w+'], methods: ['POST'])]
    public function activateAction(string $token): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::activateAction',
            ['token' => $token],
        );
    }

    /**
     * @param string $token
     *
     * @return Response
     */
    #[Route('/user/{token}/verify', requirements: ['token' => '\w+'], methods: ['POST'])]
    public function verifyAction(string $token): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::verifyAction',
            ['token' => $token],
        );
    }

    /**
     * @param string $token
     *
     * @return Response
     */
    #[Route('/user/{token}/set_password', requirements: ['token' => '\w+'], methods: ['POST'])]
    public function setPasswordAction(string $token): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::setPasswordAction',
            ['token' => $token],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/change_password', methods: ['POST'])]
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
     * @return Response
     */
    #[Route('/user/reset_password', methods: ['POST'])]
    public function resetPasswordAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::resetPasswordAction');
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user/{id}/delete', methods: ['DELETE'])]
    public function deleteAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::deleteAction',
            ['id' => $id],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/list', methods: ['POST'])]
    public function getAllUsersAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getAllUsersAction',
            [],
            $request->query->all(),
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user/{id}/saveSettings', methods: ['POST'])]
    public function saveUserSettingsAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::saveUserSettingsAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user/{id}', methods: ['GET'])]
    public function getUserAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getUserAction',
            ['id' => $id],
        );
    }

}
