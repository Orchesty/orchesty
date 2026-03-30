<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\EnterpriseUserHandler;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Traits\ControllerTrait;
use InvalidArgumentException;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class EnterpriseUserController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class EnterpriseUserController
{

    use ControllerTrait;

    /**
     * EnterpriseUserController constructor.
     *
     * @param EnterpriseUserHandler $userHandler
     */
    public function __construct(private readonly EnterpriseUserHandler $userHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/invite', methods: ['POST'], priority: 10)]
    public function inviteAction(Request $request): Response
    {
        try {
            $email = $request->request->getString('email');
            if ($email === '') {
                return $this->getErrorResponse(new InvalidArgumentException('Missing parameter "email"'), 400);
            }

            return $this->getResponse($this->userHandler->inviteUser($email));
        } catch (UserManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/add-from-account', methods: ['POST'], priority: 10)]
    public function addFromAccountAction(Request $request): Response
    {
        try {
            $email = $request->request->getString('email');
            if ($email === '') {
                return $this->getErrorResponse(new InvalidArgumentException('Missing parameter "email"'), 400);
            }

            $name = $request->request->getString('name') ?: NULL;

            return $this->getResponse($this->userHandler->addUserFromAccount($email, $name));
        } catch (UserManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user/{id}/delete', methods: ['DELETE'], priority: 10)]
    public function deleteAction(string $id): Response
    {
        try {
            return $this->getResponse($this->userHandler->deleteUser($id));
        } catch (UserManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

}
