<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Exception;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;
use Hanaboso\PipesFramework\User\Entity\TokenInterface;
use Hanaboso\PipesFramework\User\Entity\UserInterface;

/**
 * Class UserAbstract
 *
 * @package Hanaboso\PipesFramework\User\Document
 *
 */
abstract class UserAbstract implements UserInterface
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $email;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    protected $created;

    /**
     * @var TokenInterface|null
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\PipesFramework\User\Document\Token")
     */
    protected $token;

    /**
     * UserAbstract constructor.
     */
    public function __construct()
    {
        $this->created = new DateTime();
    }

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
     * @return UserInterface|User|TmpUser
     */
    public function setEmail(string $email): UserInterface
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return TokenInterface|null
     */
    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    /**
     * @param TokenInterface|null $token
     *
     * @return UserInterface
     */
    public function setToken(?TokenInterface $token): UserInterface
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return array
     */
    public function getRoles(): array
    {
        return [];
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string
     */
    public function getSalt(): string
    {
        return '';
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->email;
    }

    /**
     * Needed by symfony's UserInterface.
     */
    public function eraseCredentials(): void
    {
        throw new Exception(__CLASS__ . '::' . __METHOD__ . ' is not implemented');
    }

}