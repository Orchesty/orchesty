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
    private $systemKey;

    /**
     * @var string
     */
    private $systemName;

    /**
     * @var int
     */
    private $usersCount;

    /**
     * @var int
     */
    private $requestsCount;

    /**
     * SystemDataDto constructor.
     *
     * @param string $systemKey
     * @param string $systemName
     * @param int    $userCount
     * @param int    $requestCount
     */
    public function __construct(string $systemKey, string $systemName, int $userCount, int $requestCount)
    {
        $this->systemKey     = $systemKey;
        $this->systemName    = $systemName;
        $this->usersCount    = $userCount;
        $this->requestsCount = $requestCount;
    }

    /**
     * @return string
     */
    public function getSystemKey(): string
    {
        return $this->systemKey;
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
    public function getUsersCount(): int
    {
        return $this->usersCount;
    }

    /**
     * @return int
     */
    public function getRequestsCount(): int
    {
        return $this->requestsCount;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'system_key'     => $this->systemKey,
            'system_name'    => $this->systemName,
            'users_count'    => $this->usersCount,
            'requests_count' => $this->requestsCount,
        ];
    }

}