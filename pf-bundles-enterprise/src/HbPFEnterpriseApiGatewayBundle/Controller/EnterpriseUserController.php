<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\EnterpriseUserHandler;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Traits\ControllerTrait;
use InvalidArgumentException;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        private readonly EnterpriseUserHandler $userHandler,
        private readonly TokenStorageInterface $tokenStorage,
    )
    {
        $this->logger = new NullLogger();
    }

    /**
     * If the request reaches this action, the Auth0Authenticator firewall
     * has already verified the JWT and confirmed the user exists in MongoDB.
     *
     * @return Response
     */
    #[Route('/user/whoami', methods: ['GET'], priority: 10)]
    public function whoamiAction(): Response
    {
        $token = $this->tokenStorage->getToken();
        $user  = $token?->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'email' => $user->getUserIdentifier(),
        ]);
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

            $data     = $request->request->all();
            $groupIds = isset($data['groups']) && is_array($data['groups']) ? $data['groups'] : [];

            return $this->getResponse($this->userHandler->inviteUser($email, $groupIds));
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
     * Overrides the vendor UserBundle's resetPasswordAction to send
     * the forgot-password email via Orchesty topology instead of Symfony Mailer.
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/reset_password', methods: ['POST'], priority: 10)]
    public function resetPasswordAction(Request $request): Response
    {
        try {
            $email = $request->request->getString('email');
            if ($email === '') {
                return $this->getErrorResponse(new InvalidArgumentException('Missing parameter "email"'), 400);
            }

            return $this->getResponse($this->userHandler->forgotPassword($email));
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string  $userId
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/{userId}/role', methods: ['PUT'], priority: 10)]
    public function setRoleAction(string $userId, Request $request): Response
    {
        try {
            $role = $request->request->getString('role');
            if ($role === '') {
                return $this->getErrorResponse(new InvalidArgumentException('Missing parameter "role"'), 400);
            }

            return $this->getResponse($this->userHandler->setUserRole($userId, $role));
        } catch (InvalidArgumentException $e) {
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
