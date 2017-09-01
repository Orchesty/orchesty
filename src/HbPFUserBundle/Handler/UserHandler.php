<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Acl\Enum\ResourceEnum;
use Hanaboso\PipesFramework\HbPFAclBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\DatabaseManager\UserDatabaseManagerLocator;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
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
    private $dm;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * UserHandler constructor.
     *
     * @param UserDatabaseManagerLocator $userDml
     * @param UserManager                $userManager
     * @param ResourceProvider           $provider
     */
    public function __construct(
        UserDatabaseManagerLocator $userDml,
        UserManager $userManager,
        ResourceProvider $provider
    )
    {
        $this->dm          = $userDml->get();
        $this->userManager = $userManager;
        $this->provider    = $provider;
    }

    /**
     * @param array $data
     *
     * @return UserInterface
     */
    public function login(array $data): UserInterface
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
     * @return UserInterface
     */
    public function delete(string $id): UserInterface
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
     * @return UserInterface
     * @throws UserManagerException
     */
    private function getUser(string $id): UserInterface
    {
        /** @var UserInterface $user */
        $user = $this->dm->getRepository(
            $this->provider->getResource(ResourceEnum::USER)
        )->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User \'%s\' not exists.', $id),
                UserManagerException::USER_NOT_EXISTS
            );
        }

        return $user;
    }

}