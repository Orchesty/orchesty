<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Hanaboso\PipesFramework\Commons\Traits\Entity\DeletedTrait;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;

/**
 * Class User
 *
 * @package Hanaboso\PipesFramework\User\Entity
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Hanaboso\PipesFramework\User\Repository\Entity\UserRepository")
 */
class User extends UserAbstract
{

    use DeletedTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="date")
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
            'password' => $this->getPassword(),
        ];
    }

}