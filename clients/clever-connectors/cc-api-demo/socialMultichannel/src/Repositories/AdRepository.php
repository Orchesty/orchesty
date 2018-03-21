<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Repositories;

use CleverCore\SocialMultichannel\Entities\Ad;
use Doctrine\ORM\EntityRepository;

/**
 * Class AdRepository
 *
 * @package CleverCore\SocialMultichannel\Repositories
 */
class AdRepository extends EntityRepository
{

    /**
     * @param string $id
     *
     * @return Ad
     */
    public function getById(string $id): Ad
    {
        /** @var Ad $ad */
        $ad = $this->findOneBy(['id' => $id]);

        return $ad;
    }

}