<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Entities;

use CleverCore\Commons\Traits\IdTrait;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Ad
 *
 * @package CleverCore\SocialMultichannel\Entities
 *
 * @ORM\Table("ad")
 * @ORM\Entity(repositoryClass="CleverCore\SocialMultichannel\Repositories\AdRepository")
 */
class Ad
{

    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $audienceMirrorId;

    /**
     * @var string
     *
     * @ORM\Column(type="AdTypeEnum")
     */
    private $adType;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $settings;

    /**
     * @var Audience|NULL
     *
     * @ORM\ManyToOne(targetEntity="CleverCore\SocialMultichannel\Entities\Audience", inversedBy="ads")
     */
    private $audience;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $refId;

    /**
     * @return string
     */
    public function getAudienceMirrorId(): string
    {
        return $this->audienceMirrorId;
    }

    /**
     * @param string $audienceMirrorId
     *
     * @return Ad
     */
    public function setAudienceMirrorId(string $audienceMirrorId): Ad
    {
        $this->audienceMirrorId = $audienceMirrorId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdType(): string
    {
        return $this->adType;
    }

    /**
     * @param string $adType
     *
     * @return Ad
     */
    public function setAdType(string $adType): Ad
    {
        $this->adType = AdTypeEnum::isValid($adType);

        return $this;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return json_decode($this->settings, TRUE);
    }

    /**
     * @param array $settings
     *
     * @return Ad
     */
    public function setSettings(array $settings): Ad
    {
        $this->settings = json_encode($settings);

        return $this;
    }

    /**
     * @return Audience|NULL
     */
    public function getAudience(): ?Audience
    {
        return $this->audience;
    }

    /**
     * @param Audience|NULL $audience
     *
     * @return Ad
     */
    public function setAudience(?Audience $audience): Ad
    {
        $this->audience = $audience;

        return $this;
    }

    /**
     * @return string
     */
    public function getRefId(): string
    {
        return $this->refId;
    }

    /**
     * @param string $refId
     *
     * @return Ad
     */
    public function setRefId(string $refId): Ad
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(
            $this->getSettings(),
            [
                'id'   => $this->id,
                'type' => $this->adType,
            ]
        );
    }

}