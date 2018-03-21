<?php declare(strict_types=1);

namespace CleverCore\Commons\Entities;

use CleverCore\Commons\Enums\DirectorySourceEnum;
use CleverCore\Commons\Traits\DescriptionTrait;
use CleverCore\Commons\Traits\IdTrait;
use CleverCore\Commons\Traits\LabelTrait;
use CleverCore\Commons\Traits\LeftTrait;
use CleverCore\Commons\Traits\LevelTrait;
use CleverCore\Commons\Traits\RightTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Directory
 *
 * @package CleverCore\Commons\Entities
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="directory")
 * @ORM\Entity(repositoryClass="CleverCore\Commons\Repositories\DirectoryRepository")
 */
class Directory
{

    use IdTrait;
    use DescriptionTrait;
    use LabelTrait;
    use LeftTrait;
    use LevelTrait;
    use RightTrait;

    /**
     * @var Directory
     *
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="Directory")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    /**
     * @var Directory|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Directory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @var Directory[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Directory", mappedBy="parent")
     */
    private $children;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $clientId;

    /**
     * @var string
     *
     * @ORM\Column(type="DirectorySourceEnum", nullable=true)
     */
    private $source;

    /**
     * Directory constructor.
     *
     * @param string $label
     */
    public function __construct(string $label)
    {
        $this->label    = $label;
        $this->children = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Collection $children
     */
    public function setChildren(Collection $children): void
    {
        $this->children = $children;
    }

    /**
     * @return Directory
     */
    public function getRoot(): Directory
    {
        return $this->root;
    }

    /**
     * @param Directory|null $parent
     *
     * @return Directory
     */
    public function setParent(?Directory $parent = NULL): Directory
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Directory|null
     */
    public function getParent(): ?Directory
    {
        return $this->parent;
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
     * @return Directory
     */
    public function setClientId(string $clientId): Directory
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     *
     * @return Directory
     */
    public function setSource(string $source): Directory
    {
        $this->source = DirectorySourceEnum::isValid($source);

        return $this;
    }

}