<?php declare(strict_types=1);

namespace CcApi\ApiEntity;

/**
 * Class Subscriber
 *
 * @package CcApi\ApiEntity
 */
class Subscriber
{

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var string
     */
    protected $foreignId;

    /**
     * @var bool
     */
    protected $reactivate;

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
     * @return $this
     */
    public function setEmail(string $email): self
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
     * @return $this
     */
    public function setFirstName(string $firstName): self
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
     * @return $this
     */
    public function setLastName(string $lastName): self
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
     * @param string|int $foreignId
     *
     * @return $this
     */
    public function setForeignId($foreignId): self
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
     * @return $this
     */
    public function setReactivate(bool $reactivate): self
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
     * @return Subscriber
     */
    public function setLists(array $lists): Subscriber
    {
        $this->lists = $lists;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'email'       => $this->email,
            'first_name'  => $this->firstName,
            'last_name'   => $this->lastName,
            '_foreign_id' => $this->foreignId,
            'reactivate'  => $this->reactivate,
            'lists'       => $this->lists,
        ];
    }

}