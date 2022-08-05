<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/user/login", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $jwt
     *
     * @return Response
     * @throws PipesFrameworkException
     * @throws SecurityManagerException
     */
    public function loginUserAction(Request $request, string $jwt): Response
    {
        return $this->getResponse(
            $this->userHandler->login(array_merge($request->request->all(), ['license' => $jwt])),
        );
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
     * @Route("/user/logged_user", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function loggedUserAction(): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::loggedUserAction');
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
     * @Route("/user/{token}/verify", requirements={"token": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
    public function verifyAction(string $token): Response
    {
        return $this->forward('Hanaboso\UserBundle\Controller\UserController::verifyAction', ['token' => $token]);
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
            'Hanaboso\UserBundle\Controller\UserController::setPasswordAction',
            ['token' => $token],
        );
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
        return $this->forward(
            'Hanaboso\UserBundle\Controller\UserController::deleteAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/user/list", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws MongoDBException
     */
    public function getAllUsersAction(Request $request): Response
    {
        return $this->getResponse(
            $this->userHandler->getAllUsers(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @Route("/user/{id}/saveSettings", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     * @throws MongoDBException
     * @throws PipesFrameworkException
     * @throws UserManagerException
     */
    public function saveUserSettingsAction(Request $request, string $id): Response
    {
        return $this->getResponse($this->userHandler->saveSettings($request->request->all(), $id));
    }

    /**
     * @Route("/user/{id}", methods={"GET", "OPTIONS"}, priority="-1000")
     *
     * @param string $id
     *
     * @return Response
     * @throws UserManagerException
     */
    public function getUserAction(string $id): Response
    {
        return $this->getResponse($this->userHandler->getUserDetail($id));
    }

}
