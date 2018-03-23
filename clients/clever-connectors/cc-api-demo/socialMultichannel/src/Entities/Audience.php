<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Entities;

use CleverCore\Commons\Traits\IdTrait;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class Audience
 *
 * @package CleverCore\SocialMultichannel\Entities
 *
 * @ORM\Table("audience")
 * @ORM\Entity(repositoryClass="CleverCore\SocialMultichannel\Repositories\AudienceRepository")
 */
class Audience
{

    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="AudienceSourceEnum")
     */
    private $sourceType;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $listId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $segmentId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $clientId;

    /**
     * @var Ad[]|Collection
     *
     * @ORM\OneToMany(targetEntity="CleverCore\SocialMultichannel\Entities\ad", mappedBy="audience")
     */
    private $ads;

    /**
     * Audience constructor.
     */
    public function __construct()
    {
        $this->ads = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Audience
     */
    public function setName(string $name): Audience
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    /**
     * @param string $sourceType
     *
     * @return Audience
     */
    public function setSourceType(string $sourceType): Audience
    {
        $this->sourceType = AudienceSourceEnum::isValid($sourceType);

        return $this;
    }

    /**
     * @return string
     */
    public function getListId(): string
    {
        return $this->listId;
    }

    /**
     * @param string $listId
     *
     * @return Audience
     */
    public function setListId(string $listId): Audience
    {
        $this->listId = $listId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSegmentId(): string
    {
        return $this->segmentId;
    }

    /**
     * @param string $segmentId
     *
     * @return Audience
     */
    public function setSegmentId(string $segmentId): Audience
    {
        $this->segmentId = $segmentId;

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
     * @return Audience
     */
    public function setClientId(string $clientId): Audience
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return Ad[]|Collection
     */
    public function getAds(): iterable
    {
        return $this->ads;
    }

}