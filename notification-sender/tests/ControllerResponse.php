<?php declare(strict_types=1);

namespace Tests;

/**
 * Class ControllerResponse
 *
 * @package Tests
 */
final class ControllerResponse
{

    /**
     * @var int
     */
    private $status;

    /**
     * @var mixed[]
     */
    private $content;

    /**
     * ControllerResponse constructor.
     *
     * @param int     $status
     * @param mixed[] $content
     */
    public function __construct(int $status, array $content)
    {
        $this->status  = $status;
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return mixed[]
     */
    public function getContent(): array
    {
        return $this->content;
    }

}