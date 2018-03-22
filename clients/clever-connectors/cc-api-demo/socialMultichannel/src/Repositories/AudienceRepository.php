<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Repositories;

use CleverCore\SocialMultichannel\Entities\Audience;
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

}