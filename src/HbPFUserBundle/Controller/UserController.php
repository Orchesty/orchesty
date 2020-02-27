<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Controller
 */
class UserController
{

    use ControllerTrait;

    /**
     * @var UserHandler
     */
    private UserHandler $userHandler;

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
     * @Route("/user/login", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function loginUserAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->userHandler->login($request->request->all()));
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (PipesFrameworkException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/user/list", methods={"POST", "OPTIONS"})
     * @param Request $request
     *
     * @return Response
     */
    public function getAllUsersAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->userHandler->getAllUsers(new GridRequestDto($request->request->all())));
        } catch (MongoDBException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/user/{id}/saveSettings", methods={"POST", "OPTIONS"})
     * @param Request $request
     *
     * @param string  $id
     *
     * @return Response
     */
    public function saveUserSettingsAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->userHandler->saveSettings($request->request->all(), $id));
        } catch (MongoDBException | UserManagerException | PipesFrameworkException $e) {
            return $this->getErrorResponse($e);
        }
    }

}
