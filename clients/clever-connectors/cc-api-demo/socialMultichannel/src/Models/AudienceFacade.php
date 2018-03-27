<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models;

use CleverCore\SocialMultichannel\Documents\AudienceMirror;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Repositories\AudienceMirrorRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
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
     * @var DocumentManager
     */
    private $dm;

    /**
     * AudienceFacade constructor.
     *
     * @param EntityManager   $em
     * @param DocumentManager $dm
     */
    public function __construct(EntityManager $em, DocumentManager $dm)
    {
        $this->em = $em;
        $this->dm = $dm;
    }

    /**
     * @param array $data
     *
     * @return Audience
     */
    public function createAudience(array $data): Audience
    {
        $audience = new Audience();
        $this->fromArray($audience, $data);
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
    }

    /**
     * @param Audience $audience
     */
    public function deleteAction(Audience $audience): void
    {
        foreach ($audience->getAds() as $ad) {
            $ad->setSettings([]);
        }
        /** @var AudienceMirrorRepository $mirrorRepo */
        $mirrorRepo = $this->dm->getRepository(AudienceMirror::class);
        foreach ($mirrorRepo->getByAudience($audience) as $mirror) {
            $this->dm->remove($mirror);
        }
        $this->em->remove($audience);
        $this->dm->flush();
        $this->em->flush();
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
        $audience->setName($data['name']);
        $audience->setSourceType($data['sourceType']);
        $audience->setListId($data['listId']);
        $audience->setClientId($data['clientId']);
        $audience->setSegmentId($data['segmentId']);

        return $audience;
    }

}