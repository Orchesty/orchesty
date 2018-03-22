<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class AudienceMirror
 *
 * @package CleverCore\SocialMultichannel\Documents
 *
 * @ODM\Document(repositoryClass="CleverCore\SocialMultichannel\Repositories\AudienceMirrorRepository")
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
     * @var string
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
     * @var array
     *
     * @ODM\EmbedMany(targetDocument="EmbedSubscriber")
     */
    private $embedSubscribers = [];

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
     * @return string
     */
    public function getAdsId(): string
    {
        return $this->adsId;
    }

    /**
     * @param string $adsId
     *
     * @return AudienceMirror
     */
    public function setAdsId(string $adsId): AudienceMirror
    {
        $this->adsId = $adsId;

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
        return $this->embedSubscribers;
    }

    /**
     * @param array $embedSubscribers
     *
     * @return AudienceMirror
     */
    public function setEmbedSubscribers(array $embedSubscribers): AudienceMirror
    {
        $this->embedSubscribers = $embedSubscribers;

        return $this;
    }

}