<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models;

use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use Doctrine\ORM\EntityManager;

/**
 * Class AdFacade
 *
 * @package CleverCore\SocialMultichannel\Models
 */
class AdFacade
{

    /**
     * @var AdModuleLoader
     */
    private $loader;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * AdFacade constructor.
     *
     * @param EntityManager  $em
     * @param AdModuleLoader $loader
     */
    public function __construct(EntityManager $em, AdModuleLoader $loader)
    {
        $this->loader = $loader;
        $this->em     = $em;
    }

    /**
     * @param Audience $audience
     * @param string   $type
     * @param array    $data
     *
     * @return Ad
     */
    public function createAd(Audience $audience, string $type, array $data): Ad
    {
        $data['audience'] = $audience->toArray();

        $module = $this->loader->loadModule(AdTypeEnum::isValid($type));
        //TODO Where to conjure up userId ??
        $ad = $module->createAd($data, 'cli', $audience->getClientId());

        $ad->setAudience($audience)
            ->setClientId($audience->getClientId());
        $this->em->flush();

        return $ad;
    }

    /**
     * @param Ad    $ad
     * @param array $data
     *
     * @return Ad
     */
    public function updateAd(Ad $ad, array $data): Ad
    {
        $module = $this->loader->loadModule(AdTypeEnum::isValid($ad->getAdType()));

        return $module->updateAd($ad, $data);
    }

    /**
     * @param Ad $ad
     */
    public function deleteAd(Ad $ad): void
    {
        $module = $this->loader->loadModule(AdTypeEnum::isValid($ad->getAdType()));
        //TODO Where to conjure up userId ??
        $module->deleteAd($ad, 'cli');
    }

    /**
     * @param string $clientId
     * @param string $type
     *
     * @return array
     */
    public function getUnprocessed(string $clientId, string $type): array
    {
        $module = $this->loader->loadModule(AdTypeEnum::isValid($type));

        return $module->getUnprocessed($clientId);
    }

}