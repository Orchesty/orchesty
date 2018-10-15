<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Handlers;

use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Models\AdFacade;
use CleverCore\SocialMultichannel\Repositories\AdRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;

/**
 * Class FacebookaudienceHandler
 *
 * @package CleverCore\SocialMultichannel\Handlers
 */
class FacebookaudienceHandler
{

    /**
     * @var AdFacade
     */
    private $facade;

    /**
     * @var AdRepository
     */
    private $repo;

    /**
     * FacebookaudienceHandler constructor.
     *
     * @param AdFacade      $facade
     * @param EntityManager $em
     */
    public function __construct(AdFacade $facade, EntityManager $em)
    {
        $this->facade = $facade;
        /** @var AdRepository $repo */
        $repo       = $em->getRepository(Ad::class);
        $this->repo = $repo;
    }

    /**
     * @param string $clientId
     * @param string $adId
     * @param array  $data
     *
     * @throws ORMException
     */
    public function updateStatus(string $clientId, string $adId, array $data): void
    {
        $ad = $this->repo->getById($adId, $clientId);
        $this->facade->updateAd($ad, $data);
    }

    /**
     * @param string $clientId
     *
     * @return array
     */
    public function getUnprocessed(string $clientId): array
    {
        return $this->facade->getUnprocessed($clientId, AdTypeEnum::FB);
    }

}