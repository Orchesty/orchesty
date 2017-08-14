<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Traits\IdTrait;

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
     * @var string
     *
     * @ODM\Id(strategy="UUID", type="string")
     */
    private $uuid;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $created;

    /**
     * @var User
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\PipesFramework\User\Document\User")
     */
    private $user;

    /**
     * @var TmpUser
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\PipesFramework\User\Document\TmpUser")
     */
    private $tmpUser;

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     *
     * @return Token
     */
    public function setCreated(DateTime $created): Token
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Token
     */
    public function setUser(User $user): Token
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return TmpUser
     */
    public function getTmpUser(): TmpUser
    {
        return $this->tmpUser;
    }

    /**
     * @param TmpUser $tmpUser
     *
     * @return Token
     */
    public function setTmpUser(TmpUser $tmpUser): Token
    {
        $this->tmpUser = $tmpUser;

        return $this;
    }

}