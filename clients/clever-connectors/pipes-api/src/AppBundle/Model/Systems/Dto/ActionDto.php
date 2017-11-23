<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Dto;

/**
 * Class ActionDto
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Dto
 */
class ActionDto
{

    /**
     * @var string
     */
    private $action;

    /**
     * @var null|string
     */
    private $direction;

    /**
     * ActionDto constructor.
     *
     * @param string      $action
     * @param null|string $direction
     */
    public function __construct(string $action, ?string $direction = NULL)
    {
        $this->action    = $action;
        $this->direction = $direction;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return null|string
     */
    public function getDirection(): ?string
    {
        return $this->direction;
    }

}