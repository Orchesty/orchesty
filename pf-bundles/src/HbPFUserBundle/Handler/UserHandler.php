<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\User\UserManager;
use Hanaboso\PipesFramework\User\Model\User\UserManagerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Class UserHandler
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Handler
 */
class UserHandler implements LogoutSuccessHandlerInterface, EventSubscriberInterface
{

    /**
     * @var DocumentManager|EntityManager
     */
    private $databaseManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * UserHandler constructor.
     *
     * @param DatabaseManagerLocator $databaseManagerLocator
     * @param UserManager            $userManager
     */
    public function __construct(DatabaseManagerLocator $databaseManagerLocator, UserManager $userManager)
    {
        $this->databaseManager = $databaseManagerLocator->getDm();
        $this->userManager     = $userManager;
    }

    /**
     * @param array $data
     *
     * @return User
     */
    public function login(array $data): User
    {
        return $this->userManager->login($data);
    }

    /**
     * @return array
     */
    public function logout(): array
    {
        $this->userManager->logout();

        return [];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function register(array $data): array
    {
        $this->userManager->register($data);

        return [];
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function activate(string $id): array
    {
        $this->userManager->activate($id);

        return [];
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     */
    public function setPassword(string $id, array $data): array
    {
        $this->userManager->setPassword($id, $data);

        return [];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function resetPassword(array $data): array
    {
        $this->userManager->resetPassword($data);

        return [];
    }

    /**
     * @param string $id
     *
     * @return User
     */
    public function delete(string $id): User
    {
        return $this->userManager->delete($this->getUser($id));
    }

    /**
     * Don't redirect after logout
     *
     * @param Request $request
     *
     * @return bool
     */
    public function onLogoutSuccess(Request $request): bool
    {
        return TRUE;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onCoreException', 1000],
            ],
        ];
    }

    /**
     * Don't redirect when not authenticated
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onCoreException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException || $exception instanceof AuthenticationCredentialsNotFoundException) {
            $jsonResponse = new JsonResponse($exception->getMessage(), 403);

            $event->setResponse($jsonResponse);
        }
    }

    /**
     * @param string $id
     *
     * @return User
     * @throws UserManagerException
     */
    private function getUser(string $id): User
    {
        $user = $this->databaseManager->getRepository(User::class)->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User \'%s\' not exists.', $id),
                UserManagerException::USER_NOT_EXISTS
            );
        }

        return $user;
    }

}