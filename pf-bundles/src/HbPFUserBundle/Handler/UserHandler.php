<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Handler;

use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\User\UserManager;
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
     * @var UserManager
     */
    private $userManager;

    /**
     * UserHandler constructor.
     *
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
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
     *
     */
    public function logout(): void
    {
        $this->userManager->logout();
    }

    /**
     * @param array $data
     */
    public function register(array $data): void
    {
        $this->userManager->register($data);
    }

    /**
     * @param string $id
     */
    public function activate(string $id): void
    {
        $this->userManager->activate($id);
    }

    /**
     * @param string $id
     * @param array  $data
     */
    public function setPassword(string $id, array $data): void
    {
        $this->userManager->setPassword($id, $data);
    }

    /**
     * @param array $data
     */
    public function resetPassword(array $data): void
    {
        $this->userManager->resetPassword($data);
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

}