<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Traits\Document\DeletedTrait;
use Hanaboso\PipesFramework\User\Entity\TmpUserInterface;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;

/**
 * Class User
 *
 * @package Hanaboso\PipesFramework\User\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\User\Repository\Document\UserRepository")
 */
class User extends UserAbstract
{

    use DeletedTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $password;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $updated;

    /**
     * @param TmpUserInterface $tmpUser
     *
     * @return UserInterface
     */
    public static function from(TmpUserInterface $tmpUser): UserInterface
    {
        $user = (new self())
            ->setEmail($tmpUser->getEmail());

        return $user;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return UserTypeEnum::USER;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return UserInterface
     */
    public function setPassword(string $password): UserInterface
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * @param DateTime $updated
     *
     * @return UserInterface
     */
    public function setUpdated(DateTime $updated): UserInterface
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'       => $this->getId(),
            'email'    => $this->getEmail(),
        ];
    }

}

