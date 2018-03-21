<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait ContentIdTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait ContentIdTrait
{

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $contentId;

    /**
     * @return string
     */
    public function getContentId(): string
    {
        return $this->contentId;
    }

    /**
     * @param string $contentId
     *
     * @return self
     */
    public function setContentId(string $contentId): self
    {
        $this->contentId = $contentId;

        return $this;
    }

}