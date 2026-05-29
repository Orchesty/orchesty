<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Controller
 */
final class UserController extends AbstractController
{

    use ControllerTrait;

    /**
     * UserController constructor.
     *
     * @param UserHandler $userHandler
     */
    public function __construct(private UserHandler $userHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return Response
     */
    #[Route('/user/exists', methods: ['GET'])]
    public function userExistsAction(): Response
    {
        return $this->getResponse($this->userHandler->hasUser());
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    #[Route('/user/setup', methods: ['POST'])]
    public function setupAction(Request $request): Response
    {
        return $this->getResponse(
            $this->userHandler->setupUser($request->request->all()),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws PipesFrameworkException
     * @throws SecurityManagerException
     */
    #[Route('/user/login', methods: ['POST'])]
    public function loginUserAction(Request $request): Response
    {
        return $this->getResponse(
            $this->userHandler->login($request->request->all()),
        );
    }

    /**
     * @return Response
     */
    #[Route('/user/logout', methods: ['POST'])]
    public function logoutAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::logoutAction');
    }

    /**
     * @return Response
     */
    #[Route('/user/logged_user', methods: ['GET'])]
    public function loggedUserAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::loggedUserAction');
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws PipesFrameworkException
     */
    #[Route('/user/invite', methods: ['POST'])]
    public function inviteAction(Request $request): Response
    {
        $data = $request->request->all();
        ControllerUtils::checkParameters(['email'], $data);

        return $this->getResponse(
            $this->userHandler->inviteUser($request->request->getString('email')),
        );
    }

    /**
     * @return Response
     */
    #[Route('/user/register', methods: ['POST'])]
    public function registerAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::registerAction');
    }

    /**
     * @param string $token
     *
     * @return Response
     */
    #[Route('/user/{token}/activate', requirements: ['token' => '\w+'], methods: ['POST'])]
    public function activateAction(string $token): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::activateAction', ['token' => $token]);
    }

    /**
     * @param string $token
     *
     * @return Response
     */
    #[Route('/user/{token}/verify', requirements: ['token' => '\w+'], methods: ['POST'])]
    public function verifyAction(string $token): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::verifyAction', ['token' => $token]);
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
            'Hanaboso\UserBundle\Controller\UserController::setPasswordAction',
            ['token' => $token],
        );
    }

    /**
     * @return Response
     */
    #[Route('/user/change_password', methods: ['POST'])]
    public function changePasswordAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::changePasswordAction');
    }

    /**
     * @return Response
     */
    #[Route('/user/reset_password', methods: ['POST'])]
    public function resetPasswordAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::resetPasswordAction');
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
            'Hanaboso\UserBundle\Controller\UserController::deleteAction',
            ['id' => $id],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws MongoDBException
     */
    #[Route('/user/list', methods: ['POST'])]
    public function getAllUsersAction(Request $request): Response
    {
        return $this->getResponse(
            $this->userHandler->getAllUsers(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws MongoDBException
     */
    #[Route('/user/invited/list', methods: ['POST'])]
    public function getAllInvitedUsersAction(Request $request): Response
    {
        return $this->getResponse(
            $this->userHandler->getAllInvitedUsers(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     * @throws UserManagerException
     */
    #[Route('/user/invited/{id}/regenerate', methods: ['POST'])]
    public function regenerateInviteAction(string $id): Response
    {
        return $this->getResponse($this->userHandler->regenerateInvite($id));
    }

    /**
     * @param string $id
     *
     * @return Response
     * @throws MongoDBException
     * @throws UserManagerException
     */
    #[Route('/user/invited/{id}/delete', methods: ['DELETE'])]
    public function deleteInvitedUserAction(string $id): Response
    {
        $this->userHandler->deleteInvitedUser($id);

        return $this->getResponse([]);
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     * @throws MongoDBException
     * @throws PipesFrameworkException
     * @throws UserManagerException
     */
    #[Route('/user/{id}/saveSettings', methods: ['POST'])]
    public function saveUserSettingsAction(Request $request, string $id): Response
    {
        return $this->getResponse($this->userHandler->saveSettings($request->request->all(), $id));
    }

    /**
     * @param string $id
     *
     * @return Response
     * @throws UserManagerException
     */
    #[Route('/user/{id}', methods: ['GET'], priority: -1_000)]
    public function getUserAction(string $id): Response
    {
        return $this->getResponse($this->userHandler->getUserDetail($id));
    }

}
