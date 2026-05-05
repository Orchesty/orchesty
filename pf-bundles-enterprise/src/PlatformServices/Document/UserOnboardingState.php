<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class UserOnboardingState
 *
 * Per-user onboarding-state record. Currently tracks a single flag:
 * `welcomeSeenAt` — when the user dismissed the dashboard welcome modal.
 * Kept as a dedicated lightweight document (rather than packed into the
 * generic UserSettings hash) so future onboarding hints can be added
 * without merging into a free-form blob owned by the core UserHandler.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document
 */
#[ODM\Document(
    repositoryClass: 'Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\UserOnboardingStateRepository',
)]
#[ODM\UniqueIndex(keys: ['userId' => 'asc'])]
final class UserOnboardingState
{

    use IdTrait;

    #[ODM\Field(type: 'string')]
    private string $userId;

    #[ODM\Field(type: 'date', nullable: TRUE)]
    private ?DateTime $welcomeSeenAt = NULL;

    #[ODM\Field(type: 'date')]
    private DateTime $createdAt;

    #[ODM\Field(type: 'date')]
    private DateTime $updatedAt;

    /**
     * UserOnboardingState constructor.
     *
     * @param string $userId
     */
    public function __construct(string $userId)
    {
        $now             = new DateTime('now', new DateTimeZone('UTC'));
        $this->userId    = $userId;
        $this->createdAt = $now;
        $this->updatedAt = clone $now;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @return DateTime|null
     */
    public function getWelcomeSeenAt(): ?DateTime
    {
        return $this->welcomeSeenAt;
    }

    /**
     * @return UserOnboardingState
     */
    public function markWelcomeSeen(): self
    {
        $now                 = new DateTime('now', new DateTimeZone('UTC'));
        $this->welcomeSeenAt = $now;
        $this->updatedAt     = clone $now;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'welcomeSeenAt' => $this->welcomeSeenAt?->format(DateTimeInterface::ATOM),
        ];
    }

}
