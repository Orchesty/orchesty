<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Repositories;

use CleverCore\SocialMultichannel\Entities\Ad;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

/**
 * Class AdRepository
 *
 * @package CleverCore\SocialMultichannel\Repositories
 */
class AdRepository extends EntityRepository
{

    /**
     * @param string $id
     * @param string $clientId
     *
     * @return Ad
     * @throws ORMException
     */
    public function getById(string $id, string $clientId): Ad
    {
        /** @var Ad $ad */
        $ad = $this->findOneBy([
            'id'       => $id,
            'clientId' => $clientId,
        ]);

        if (!$ad) {
            throw new ORMException(
                sprintf('Ad with given id [%s] does not exist or client [%s] is not an owner.', $id, $clientId)
            );
        }

        return $ad;
    }

    /**
     * @param string $clientId
     * @param string $type
     *
     * @return array
     */
    public function getUnprocessed(string $clientId, string $type): array
    {
        $ads = $this->findBy([
            'clientId' => $clientId,
            'adType'   => $type,
        ]);

        $res = [];
        /** @var Ad $ad */
        foreach ($ads as $ad) {
            $tmp = $ad->toArray();
            if ($tmp['status'] ?? '' !== 'ACTIVE') {
                $res[] = $tmp;
            }
        }

        return $res;
    }

}