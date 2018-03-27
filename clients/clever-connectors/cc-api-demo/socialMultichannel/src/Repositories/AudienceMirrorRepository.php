<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Repositories;

use CleverCore\SocialMultichannel\Entities\Audience;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class AudienceMirrorRepository
 *
 * @package CleverCore\SocialMultichannel\Repositories
 */
class AudienceMirrorRepository extends DocumentRepository
{

    /**
     * @param Audience $audience
     *
     * @return array
     */
    public function getByAudience(Audience $audience): array
    {
        return $this->findBy(['audienceId' => $audience->getId()]);
    }

}