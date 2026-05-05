<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use DateTimeInterface;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service\UserOnboardingService;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

/**
 * Class UserOnboardingController
 *
 * Per-user onboarding-state endpoints used by the dashboard welcome modal.
 * Both routes are scoped to the authenticated user — the user id is read
 * from the token, never from the URL — so a regular user can read/mark
 * their own state without needing the global USER ACL resource.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class UserOnboardingController
{

    use ControllerTrait;

    /**
     * UserOnboardingController constructor.
     *
     * @param UserOnboardingService $service
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        private readonly UserOnboardingService $service,
        private readonly TokenStorageInterface $tokenStorage,
    )
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return Response
     */
    #[Route('/onboarding/state', methods: ['GET'], priority: 10)]
    public function getStateAction(): Response
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $state = $this->service->getState($user->getId());

            return $this->getResponse([
                'welcomeSeenAt' => $state?->getWelcomeSeenAt()?->format(DateTimeInterface::ATOM),
            ]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @return Response
     */
    #[Route('/onboarding/welcome-seen', methods: ['POST'], priority: 10)]
    public function markWelcomeSeenAction(): Response
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->getResponse($this->service->markWelcomeSeen($user->getId()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
