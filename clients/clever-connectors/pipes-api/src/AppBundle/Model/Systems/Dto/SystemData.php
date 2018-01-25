<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Dto;

/**
 * Class SystemData
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Dto
 */
final class SystemData
{

    /**
     * @var string
     */
    private $systeKey;

    /**
     * @var string
     */
    private $systemName;

    /**
     * @var int
     */
    private $userCount;

    /**
     * @var int
     */
    private $requestCount;

    /**
     * SystemDataDto constructor.
     *
     * @param string $systeKey
     * @param string $systemName
     * @param int    $userCount
     * @param int    $requestCount
     */
    public function __construct(string $systeKey, string $systemName, int $userCount, int $requestCount)
    {
        $this->systeKey = $systeKey;
        $this->systemName = $systemName;
        $this->userCount = $userCount;
        $this->requestCount = $requestCount;
    }

    /**
     * @return string
     */
    public function getSysteKey(): string
    {
        return $this->systeKey;
    }

    /**
     * @return string
     */
    public function getSystemName(): string
    {
        return $this->systemName;
    }

    /**
     * @return int
     */
    public function getUserCount(): int
    {
        return $this->userCount;
    }

    /**
     * @return int
     */
    public function getRequestCount(): int
    {
        return $this->requestCount;
    }

}