<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait SubjectIdTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait SubjectIdTrait
{

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $subjectId;

    /**
     * @return string
     */
    public function getSubjectId(): string
    {
        return $this->subjectId;
    }

    /**
     * @param string $subjectId
     *
     * @return self
     */
    public function setSubjectId(string $subjectId): self
    {
        $this->subjectId = $subjectId;

        return $this;
    }

}