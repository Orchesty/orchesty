<?php declare(strict_types=1);

namespace ApplinthTests;

/**
 * Class ControllerResponse
 *
 * @package ApplinthTests
 */
final class ControllerResponse
{

    /**
     * ControllerResponse constructor.
     *
     * @param int     $status
     * @param mixed[] $content
     */
    public function __construct(private int $status, private array $content)
    {
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
