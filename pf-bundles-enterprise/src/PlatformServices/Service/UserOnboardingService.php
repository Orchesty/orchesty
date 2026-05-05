<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document\UserOnboardingState;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\UserOnboardingStateRepository;

/**
 * Class UserOnboardingService
 *
 * Read/write per-user onboarding-state flags. The `welcomeSeenAt` flag is
 * the only consumer for now (dashboard welcome modal). Kept intentionally
 * thin — no DTOs, no events — so plugging in further onboarding signals
 * later (e.g. dismissed help nudges) only adds methods, not infrastructure.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Service
 */
final class UserOnboardingService
{

    /**
     * @var ObjectRepository<UserOnboardingState>&UserOnboardingStateRepository
     */
    private ObjectRepository $repository;

    /**
     * UserOnboardingService constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(private readonly DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(UserOnboardingState::class);
    }

    /**
     * Returns the persisted record for the user or NULL when the user has
     * never written one (i.e. the welcome modal has never been dismissed).
     * Read-only — does not auto-create a row, so a fresh user is detectable
     * by a missing record without polluting the collection.
     *
     * @param string $userId
     *
     * @return UserOnboardingState|null
     */
    public function getState(string $userId): ?UserOnboardingState
    {
        return $this->repository->findByUserId($userId);
    }

    /**
     * @param string $userId
     *
     * @return array<string, string|null>
     *
     * @throws MongoDBException
     */
    public function markWelcomeSeen(string $userId): array
    {
        $state = $this->repository->findByUserId($userId);

        if (!$state instanceof UserOnboardingState) {
            $state = new UserOnboardingState($userId);
            $this->dm->persist($state);
        }

        $state->markWelcomeSeen();
        $this->dm->flush();

        return $state->toArray();
    }

}
