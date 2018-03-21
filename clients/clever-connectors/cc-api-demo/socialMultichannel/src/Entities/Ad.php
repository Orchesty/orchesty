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
 *
 */
class Ad
{

    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $audienceId;

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
     * @return string
     */
    public function getAudienceId(): string
    {
        return $this->audienceId;
    }

    /**
     * @param string $audienceId
     *
     * @return Ad
     */
    public function setAudienceId(string $audienceId): Ad
    {
        $this->audienceId = $audienceId;

        return $this;
    }

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
     * @return string
     */
    public function getSettings(): string
    {
        return $this->settings;
    }

    /**
     * @param string $settings
     *
     * @return Ad
     */
    public function setSettings(string $settings): Ad
    {
        $this->settings = $settings;

        return $this;
    }

}