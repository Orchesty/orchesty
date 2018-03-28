<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models\AdModules;

use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Models\AdModuleInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class AdModuleAbstract
 *
 * @package CleverCore\SocialMultichannel\Models\AdModules
 */
abstract class AdModuleAbstract implements AdModuleInterface
{

    protected const TYPE = '';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * AdModuleAbstract constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param array $data
     *
     * @return Ad
     */
    public function createAd(array $data): Ad
    {
        $ad = new Ad();
        $ad->setSettings($data)
            ->setAdType(static::TYPE)
            ->setAudienceMirrorId('');
        $this->em->persist($ad);
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
        $ad->setSettings($data);
        $this->em->flush();

        return $ad;
    }

    /**
     * @param Ad $ad
     */
    public function deleteAd(Ad $ad): void
    {
        $this->em->remove($ad);
        $this->em->flush();
    }

}