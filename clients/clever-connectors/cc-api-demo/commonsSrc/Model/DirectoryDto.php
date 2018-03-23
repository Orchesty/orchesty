<?php declare(strict_types=1);

namespace CleverCore\Commons\Model;

use CleverCore\Commons\Entities\Directory;

/**
 * Class DirectoryDto
 *
 * @package CleverCore\Commons\Model\Directory
 */
class DirectoryDto
{

    /**
     * @var Directory|null
     */
    private $parent;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $label;

    /**
     * @var null|string
     */
    private $description;

    /**
     * DirectoryDto constructor.
     *
     * @param Directory|null $parent
     * @param string         $clientId
     * @param string         $source
     * @param string         $label
     * @param null|string    $description
     */
    public function __construct(
        ?Directory $parent = NULL,
        string $clientId,
        string $source,
        string $label,
        ?string $description = NULL
    )
    {
        $this->parent      = $parent;
        $this->clientId    = $clientId;
        $this->source      = $source;
        $this->label       = $label;
        $this->description = $description;
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
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

}