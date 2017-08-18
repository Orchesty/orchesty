<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Traits\IdTrait;
use LogicException;

/**
 * Class Token
 *
 * @package Hanaboso\PipesFramework\User\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\User\Repository\TokenRepository")
 */
class Token
{

    use IdTrait;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $created;

    /**
     * @var UserInterface|User|null
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\PipesFramework\User\Document\User")
     */
    private $user;

    /**
     * @var UserInterface|TmpUser|null
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\PipesFramework\User\Document\TmpUser")
     */
    private $tmpUser;

    /**
     * Token constructor.
     */
    public function __construct()
    {
        $this->created = new DateTime();
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return UserInterface|User|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     *
     * @return Token
     */
    public function setUser(UserInterface $user): Token
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return UserInterface|TmpUser|null
     */
    public function getTmpUser(): ?UserInterface
    {
        return $this->tmpUser;
    }

    /**
     * @param UserInterface|null $tmpUser
     *
     * @return Token
     */
    public function setTmpUser(?UserInterface $tmpUser): Token
    {
        $this->tmpUser = $tmpUser;

        return $this;
    }

    /**
     * @return UserInterface|User|TmpUser
     */
    public function getUserOrTmpUser(): UserInterface
    {
        if ($this->user) {
            return $this->user;
        } elseif ($this->tmpUser) {
            return $this->tmpUser;
        } else {
            throw new LogicException('User is not set.');
        }
    }

}