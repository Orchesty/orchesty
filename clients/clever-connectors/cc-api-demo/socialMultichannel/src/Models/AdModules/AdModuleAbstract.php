<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models\AdModules;

use CcApi\Curl\Exception\CurlException;
use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Models\AdModuleInterface;
use CleverCore\SocialMultichannel\Models\PipesSender;
use CleverCore\SocialMultichannel\Repositories\AdRepository;
use Doctrine\ORM\EntityManager;

/**
 * Class AdModuleAbstract
 *
 * @package CleverCore\SocialMultichannel\Models\AdModules
 */
abstract class AdModuleAbstract implements AdModuleInterface
{

    protected const TYPE   = '';
    protected const SYSTEM = '';

    protected const CREATE_AD_URL = '%s/system/%s/user/%s/action/createAd';
    protected const DELETE_AD_URL = '%s/system/%s/user/%s/action/deleteAd';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var PipesSender
     */
    protected $sender;

    /**
     * @var string
     */
    protected $backend;

    /**
     * AdModuleAbstract constructor.
     *
     * @param EntityManager $em
     * @param PipesSender   $sender
     */
    public function __construct(EntityManager $em, PipesSender $sender)
    {
        $this->em     = $em;
        $this->sender = $sender;
    }

    /**
     * @param array  $data
     * @param string $userId
     * @param string $clientId
     *
     * @return Ad
     * @throws CurlException
     */
    public function createAd(array $data, string $userId, string $clientId): Ad
    {
        $data = $this->validateData($data);

        $ad = new Ad();
        $ad->setSettings($this->trimSettings($data))
            ->setAdType(static::TYPE)
            ->setAudienceMirrorId('')
            ->setClientId($clientId);
        $this->em->persist($ad);
        $this->em->flush();

        $data['id']   = $ad->getId();
        $data['type'] = static::TYPE;
        $this->sender->createAd(static::SYSTEM, $userId, $data);

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
        if (array_key_exists('ref_id', $data)) {
            $ad->setRefId($data['ref_id']);
            unset($data['ref_id']);
        }

        if (array_key_exists('mirr_id', $data)) {
            $ad->setAudienceMirrorId($data['mirr_id']);
            unset($data['mirr_id']);
        }

        $ad->setSettings(array_merge($ad->getSettings(), $data));
        $this->em->flush();

        return $ad;
    }

    /**
     * @param Ad     $ad
     * @param string $userId
     *
     * @throws CurlException
     */
    public function deleteAd(Ad $ad, string $userId): void
    {
        $this->em->remove($ad);
        $this->em->flush();
        $this->sender->removeMirror(static::SYSTEM, $userId, [
            'mirror_id' => $ad->getAudienceMirrorId(),
        ]);
    }

    /**
     * @param string $clientId
     *
     * @return array
     */
    public function getUnprocessed(string $clientId): array
    {
        /** @var AdRepository $repo */
        $repo = $this->em->getRepository(Ad::class);

        return $repo->getUnprocessed($clientId, static::TYPE);
    }

    /**
     * --------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param array $data
     *
     * @return array
     */
    protected function validateData(array $data): array
    {
        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function trimSettings(array $data): array
    {
        return [];
    }

}