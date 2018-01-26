<?php declare(strict_types=1);

namespace CcApi\ApiEntity;

/**
 * Class Connector
 *
 * @package CcApi\ApiEntity
 */
class Connector
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
     * @return string
     */
    public function getSystemKey(): string
    {
        return $this->systemKey;
    }

    /**
     * @param string $systemKey
     *
     * @return Connector
     */
    public function setSystemKey(string $systemKey): Connector
    {
        $this->systemKey = $systemKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getSystemName(): string
    {
        return $this->systemName;
    }

    /**
     * @param string $systemName
     *
     * @return Connector
     */
    public function setSystemName(string $systemName): Connector
    {
        $this->systemName = $systemName;

        return $this;
    }

    /**
     * @return int
     */
    public function getUsersCount(): int
    {
        return $this->usersCount;
    }

    /**
     * @param int $usersCount
     *
     * @return Connector
     */
    public function setUsersCount(int $usersCount): Connector
    {
        $this->usersCount = $usersCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getRequestsCount(): int
    {
        return $this->requestsCount;
    }

    /**
     * @param int $requestsCount
     *
     * @return Connector
     */
    public function setRequestsCount(int $requestsCount): Connector
    {
        $this->requestsCount = $requestsCount;

        return $this;
    }

}