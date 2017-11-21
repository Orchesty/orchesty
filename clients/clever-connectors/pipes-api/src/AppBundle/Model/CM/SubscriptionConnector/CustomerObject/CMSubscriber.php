<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject;

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 13.10.17
 * Time: 13:49
 */

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;

/**
 * Class CMSubscriber
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject
 */
final class CMSubscriber
{

    /**
     * @var string
     */
    private $email = '';

    /**
     * @var string
     */
    private $firstName = '';

    /**
     * @var string
     */
    private $lastName = '';

    /**
     * @var string
     */
    private $foreignId = '';

    /**
     * @var bool
     */
    private $reactivate = TRUE;

    /**
     * @var array
     */
    private $lists = [];

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return CMSubscriber
     */
    public function setEmail(string $email): CMSubscriber
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return CMSubscriber
     */
    public function setFirstName(string $firstName): CMSubscriber
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return CMSubscriber
     */
    public function setLastName(string $lastName): CMSubscriber
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getForeignId(): string
    {
        return $this->foreignId;
    }

    /**
     * @param mixed $foreignId
     *
     * @return CMSubscriber
     */
    public function setForeignId($foreignId): CMSubscriber
    {
        $this->foreignId = (string) $foreignId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReactivate(): bool
    {
        return $this->reactivate;
    }

    /**
     * @param bool $reactivate
     *
     * @return CMSubscriber
     */
    public function setReactivate(bool $reactivate): CMSubscriber
    {
        $this->reactivate = $reactivate;

        return $this;
    }

    /**
     * @return array
     */
    public function getLists(): array
    {
        return $this->lists;
    }

    /**
     * @param array $lists
     *
     * @return CMSubscriber
     */
    public function setLists(array $lists): CMSubscriber
    {
        $this->lists = $lists;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $res = [
            CleverFieldsEnum::EMAIL      => $this->email,
            CleverFieldsEnum::REACTIVATE => $this->reactivate,
        ];

        if (!empty($this->firstName)) {
            $res[CleverFieldsEnum::FIRST_NAME] = $this->firstName;
        }
        if (!empty($this->lastName)) {
            $res[CleverFieldsEnum::LAST_NAME] = $this->lastName;
        }
        if (!empty($this->foreignId)) {
            $res[CleverFieldsEnum::FOREIGN_ID] = $this->foreignId;
        }
        if (!empty($this->lists)) {
            $res[CleverFieldsEnum::LISTS] = $this->lists;
        }

        return $res;
    }

}