<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models\AdModules;

use CcApi\Curl\CurlSender;
use CcApi\Curl\Exception\CurlException;
use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Models\AdModuleInterface;
use CleverCore\SocialMultichannel\Repositories\AdRepository;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Request;

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

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var CurlSender
     */
    protected $curl;

    /**
     * @var string
     */
    protected $backend;

    /**
     * AdModuleAbstract constructor.
     *
     * @param string        $backend
     * @param EntityManager $em
     * @param CurlSender    $curl
     */
    public function __construct(string $backend, EntityManager $em, CurlSender $curl)
    {
        $this->em      = $em;
        $this->curl    = $curl;
        $this->backend = rtrim($backend, '/');
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

        $data['id'] = $ad->getId();
        $this->systemAdCreate($data, $userId);

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
        $adId = $data['ref_id'];
        unset($data['ref_id']);

        $ad->setSettings(array_merge($ad->getSettings(), $data));
        $ad->setRefId($adId);
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

    /**
     * @param string $clientId
     *
     * @return array
     */
    public function getUnprocessed(string $clientId): array
    {
        /** @var AdRepository $repo */
        $repo = $this->em->getRepository(Ad::class);

        return $repo->getUnprocessed($clientId, self::TYPE);
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

    /**
     * @param array  $data
     * @param string $userId
     *
     * @throws CurlException
     */
    protected function systemAdCreate(array $data, string $userId): void
    {
        $req = new Request(CurlSender::POST, sprintf(
            static::CREATE_AD_URL,
            $this->backend,
            static::SYSTEM,
            $userId
        ), [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], json_encode($data));

        $this->curl->send($req);
    }

}