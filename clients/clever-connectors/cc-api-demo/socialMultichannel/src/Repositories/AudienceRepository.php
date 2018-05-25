<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Repositories;

use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnum;
use Doctrine\ORM\EntityRepository;

/**
 * Class AudienceRepository
 *
 * @package CleverCore\SocialMultichannel\Repositories
 */
class AudienceRepository extends EntityRepository
{

    /**
     * @param string $id
     *
     * @return Audience
     */
    public function getById(string $id): Audience
    {
        /** @var Audience $audience */
        $audience = $this->findOneBy(['id' => $id]);

        return $audience;
    }

    /**
     * @param string $id
     *
     * @return Audience[]
     */
    public function getByList(string $id): array
    {
        return $this->findBy(['sourceType' => AudienceSourceEnum::LIST, 'listId' => $id]);
    }

    /**
     * @param string $id
     *
     * @return Audience[]
     */
    public function getBySegment(string $id): array
    {
        return $this->findBy(['sourceType' => AudienceSourceEnum::SEGMENT, 'segmentId' => $id]);
    }

}