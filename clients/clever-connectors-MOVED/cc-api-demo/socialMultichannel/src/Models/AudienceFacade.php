<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models;

use CcApi\Curl\Exception\CurlException;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use Doctrine\ORM\EntityManager;

/**
 * Class AudienceFacade
 *
 * @package CleverCore\SocialMultichannel\Models
 */
class AudienceFacade
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var PipesSender
     */
    private $sender;

    /**
     * AudienceFacade constructor.
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
     * @param array $data
     *
     * @return Audience
     */
    public function createAudience(array $data): Audience
    {
        $audience = new Audience();
        $audience = $this->fromArray($audience, $data);
        $this->em->persist($audience);
        $this->em->flush();

        return $audience;
    }

    /**
     * @param Audience $audience
     * @param array    $data
     *
     * @return Audience
     */
    public function updateAudience(Audience $audience, array $data): Audience
    {
        $this->fromArray($audience, $data);
        $this->em->flush();

        return $audience;
    }

    /**
     * @param Audience $audience
     *
     * @throws CurlException
     */
    public function deleteAudience(Audience $audience): void
    {
        $types = [];
        foreach ($audience->getAds() as $ad) {
            $ad->setSettings([]);
            if (!in_array($ad->getAdType(), $types)) {
                $types[] = $ad->getAdType();
            }
        }
        $id = $audience->getId();
        $this->em->remove($audience);
        $this->em->flush();

        $userId = '123'; //TODO UserId
        foreach ($types as $type) {
            $this->sender->removeMirror($type, $userId, [
                'audience_id' => $id,
            ]);
        }

        $this->runBatchUpdate([$audience]);
    }

    /**
     * @param Audience[] $audiences
     *
     * @throws CurlException
     */
    public function runBatchUpdate(array $audiences): void
    {
        $userId = '123'; // TODO: How to get it?
        foreach ($audiences as $audience) {
            $systems = $this->getSystemsFromAudience($audience);
            foreach ($systems as $system) {
                $data = ['audience' => $audience->toArray()]; // TODO: Is this format right?
                $this->sender->syncAudience($system !== AdTypeEnum::FB ? $system : 'facebookaudience', $userId, $data);
            }
        }
    }

    /**
     * @param string $adType
     * @param string $clientId
     * @param string $email
     */
    public function runCreate(string $adType, string $clientId, string $email): void
    {
        // TODO implement
    }

    /**
     * @param string $adType
     * @param string $clientId
     * @param string $email
     */
    public function runDelete(string $adType, string $clientId, string $email): void
    {
        // TODO implement
    }

    /**
     * @param Audience $audience
     *
     * @return array
     */
    private function getSystemsFromAudience(Audience $audience): array
    {
        $types = [];

        foreach ($audience->getAds() as $ad) {
            if (!in_array($ad->getAdType(), $types, TRUE)) {
                $types[] = $ad->getAdType();
            }
        }

        return $types;
    }

    /**
     * @param Audience $audience
     * @param array    $data
     *
     * @return Audience
     */
    private function fromArray(Audience $audience, array $data): Audience
    {
        $audience->setName($data['name'])
            ->setClientId($data['client_id'])
            ->setSourceType($data['source_type'] ?? NULL)
            ->setListId($data['distribution_list'] ?? NULL)
            ->setSegmentId($data['segment_id'] ?? NULL);

        return $audience;
    }

}