<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models;

use CleverCore\SocialMultichannel\Documents\AudienceMirror;
use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class AdFacade
 *
 * @package CleverCore\SocialMultichannel\Models
 */
class AdFacade
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var AdModuleLoader
     */
    private $loader;

    /**
     * AdFacade constructor.
     *
     * @param DocumentManager $dm
     * @param AdModuleLoader  $loader
     */
    public function __construct(DocumentManager $dm, AdModuleLoader $loader)
    {
        $this->dm     = $dm;
        $this->loader = $loader;
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
        $module = $this->loader->loadModule(AdTypeEnum::isValid($type));
        $ad     = $module->createAd($data);

        $mirr = new AudienceMirror();
        $mirr->setAudienceId($audience->getId())
            ->setClientId($audience->getClientId())
            ->setAdsId($ad->getId());

        $this->dm->persist($mirr);

        $ad->setAudience($audience)
            ->setAudienceMirrorId($mirr->getId());
        $this->dm->flush();

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
        $mirr   = $this->dm->find(AudienceMirror::class, $ad->getAudienceMirrorId());
        $this->dm->remove($mirr);
        $module->deleteAd($ad);
        $this->dm->flush();
    }

}