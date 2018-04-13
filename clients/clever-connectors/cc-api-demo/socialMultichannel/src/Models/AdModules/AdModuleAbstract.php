<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models\AdModules;

use CcApi\Curl\CurlSender;
use CcApi\Curl\Exception\CurlException;
use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Models\AdModuleInterface;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Request;

/**
 * Class AdModuleAbstract
 *
 * @package CleverCore\SocialMultichannel\Models\AdModules
 */
abstract class AdModuleAbstract implements AdModuleInterface
{

    protected const TYPE          = '';
    protected const SYSTEM        = '';

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
     *
     * @return Ad
     * @throws CurlException
     */
    public function createAd(array $data, string $userId): Ad
    {
        $ad = new Ad();
        $ad->setSettings($this->validateData($data))
            ->setAdType(static::TYPE)
            ->setAudienceMirrorId('');
        $this->em->persist($ad);
        $this->em->flush();

        $this->systemAdCreate($ad, $userId);

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
        $ad->setSettings($this->validateData(array_merge($ad->getSettings(), $data)));
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
     * @param Ad     $ad
     * @param string $userId
     *
     * @throws CurlException
     */
    protected function systemAdCreate(Ad $ad, string $userId): void
    {
        $req = new Request(CurlSender::POST, sprintf(
            static::CREATE_AD_URL,
            $this->backend,
            static::SYSTEM,
            $userId
        ), [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], json_encode($ad->toArray()));

        $this->curl->send($req);
    }

}