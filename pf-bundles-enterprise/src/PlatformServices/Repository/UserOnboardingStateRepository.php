<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document\UserOnboardingState;

/**
 * Class UserOnboardingStateRepository
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository
 *
 * @phpstan-extends DocumentRepository<UserOnboardingState>
 */
final class UserOnboardingStateRepository extends DocumentRepository
{

    /**
     * @param string $userId
     *
     * @return UserOnboardingState|null
     */
    public function findByUserId(string $userId): ?UserOnboardingState
    {
        /** @var UserOnboardingState|null $state */
        $state = $this->findOneBy(['userId' => $userId]);

        return $state;
    }

}
