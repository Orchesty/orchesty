<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models;

use CcApi\Curl\Exception\CurlException;
use CleverCore\SocialMultichannel\Entities\Audience;
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

        $this->runBatchUpdate();
    }

    /**
     *
     */
    public function runBatchUpdate(): void
    {
        // TODO implement
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
     * @param array    $data
     *
     * @return Audience
     */
    private function fromArray(Audience $audience, array $data): Audience
    {
        $audience->setName($data['name'])
            ->setClientId($data['clientId'])
            ->setSourceType($data['sourceType'] ?? NULL)
            ->setListId($data['listId'] ?? NULL)
            ->setSegmentId($data['segmentId'] ?? NULL);

        return $audience;
    }

}