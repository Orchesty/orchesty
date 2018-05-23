<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject;

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
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject
 */
final class CMSubscriber
{

    private const KEY = 'field_id';
    private const VAL = 'values';

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
     * @var bool
     */
    private $sendOptin = FALSE;

    /**
     * @var array
     */
    private $customFields = [];

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
     * @return bool
     */
    public function isSendOptin(): bool
    {
        return $this->sendOptin;
    }

    /**
     * @param bool $sendOptin
     *
     * @return CMSubscriber
     */
    public function setSendOptin(bool $sendOptin): CMSubscriber
    {
        $this->sendOptin = $sendOptin;

        return $this;
    }

    /**
     * @return array
     */
    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    /**
     * @param string $key
     * @param mixed  $val
     *
     * @return CMSubscriber
     */
    public function addCustomField(string $key, $val): CMSubscriber
    {
        $this->customFields[] = [
            self::KEY => $key,
            self::VAL => [(string) $val],
        ];

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
            CleverFieldsEnum::SEND_OPTIN => $this->sendOptin,
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

        if (!empty($this->customFields)) {
            $res[CleverFieldsEnum::FIELDS] = $this->customFields;
        }

        return $res;
    }

}