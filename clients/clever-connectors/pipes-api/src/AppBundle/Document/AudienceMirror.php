<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Class AudienceMirror
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\AudienceMirrorRepository")
 * @ODM\HasLifecycleCallbacks()
 */
class AudienceMirror
{

    /**
     * @var string
     *
     * @ODM\Id()
     */
    private $id;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $audienceId;

    /**
     * @var array|string
     *
     * @ODM\Field(type="string")
     */
    private $adsId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $clientId;

    /**
     * @var array|PersistentCollection
     *
     * @ODM\EmbedMany(targetDocument="EmbedSubscriber")
     */
    private $embedSubscribers = [];

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", nullable=true)
     */
    private $systemAudienceId = NULL;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAudienceId(): string
    {
        return $this->audienceId;
    }

    /**
     * @param string $audienceId
     *
     * @return AudienceMirror
     */
    public function setAudienceId(string $audienceId): AudienceMirror
    {
        $this->audienceId = $audienceId;

        return $this;
    }

    /**
     * @return array|string
     */
    public function getAdsId()
    {
        return $this->adsId;
    }

    /**
     * @param string $adId
     *
     * @return AudienceMirror
     */
    public function addAdId(string $adId): AudienceMirror
    {
        $this->adsId[] = $adId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     *
     * @return AudienceMirror
     */
    public function setClientId(string $clientId): AudienceMirror
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return array
     */
    public function getEmbedSubscribers(): array
    {
        return $this->embedSubscribers->toArray();
    }

    /**
     * @return array
     */
    public function getSubscribers(): array
    {
        $subs = [];
        /** @var EmbedSubscriber $sub */
        foreach ($this->embedSubscribers as $sub) {
            $subs[] = $sub->getEmail();
        }

        return $subs;
    }

    /**
     * @param EmbedSubscriber $subscriber
     *
     * @return AudienceMirror
     */
    public function addSubscriber(EmbedSubscriber $subscriber): AudienceMirror
    {
        $this->embedSubscribers[] = $subscriber;

        return $this;
    }

    /**
     * @param int $index
     *
     * @return AudienceMirror
     */
    public function removeSubscribeByIndex(int $index): AudienceMirror
    {
        $this->embedSubscribers->remove($index);

        return $this;
    }

    /**
     * @param string $email
     *
     * @return AudienceMirror
     */
    public function removeSubscriberByEmail(string $email): AudienceMirror
    {
        $index = array_search($email, $this->getSubscribers());
        $this->removeSubscribeByIndex($index);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSystemAudienceId(): ?string
    {
        return $this->systemAudienceId;
    }

    /**
     * @param string $systemAudienceId
     *
     * @return AudienceMirror
     */
    public function setSystemAudienceId(string $systemAudienceId): AudienceMirror
    {
        $this->systemAudienceId = $systemAudienceId;

        return $this;
    }

    /**
     * @ODM\PreFlush()
     */
    public function preFlush(): void
    {
        $this->adsId = json_encode($this->adsId);
    }

    /**
     * @ODM\PostLoad
     */
    public function postLoad(): void
    {
        $this->adsId = json_decode($this->adsId, TRUE);
    }

}