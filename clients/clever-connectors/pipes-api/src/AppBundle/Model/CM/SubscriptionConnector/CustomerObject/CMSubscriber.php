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
     * @var bool
     */
    private $unsubscribe = FALSE;

    /**
     * @var bool
     */
    private $hard_bounce = FALSE;

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

        return $res;
    }

    /**
     * @return bool
     */
    public function isUnsubscribe(): bool
    {
        return $this->unsubscribe;
    }

    /**
     * @param bool $unsubscribe
     *
     * @return CMSubscriber
     */
    public function setUnsubscribe(bool $unsubscribe): CMSubscriber
    {
        $this->unsubscribe = $unsubscribe;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHardBounce(): bool
    {
        return $this->hard_bounce;
    }

    /**
     * @param bool $hard_bounce
     *
     * @return CMSubscriber
     */
    public function setHardBounce(bool $hard_bounce): CMSubscriber
    {
        $this->hard_bounce = $hard_bounce;

        return $this;
    }

}