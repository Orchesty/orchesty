<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;

/**
 * Class TmpUser
 *
 * @package Hanaboso\PipesFramework\User\Entity
 *
 * @ORM\Table(name="tmp_user")
 * @ORM\Entity(repositoryClass="Hanaboso\PipesFramework\User\Repository\Entity\TmpUserRepository")
 */
class TmpUser extends UserAbstract implements TmpUserInterface
{

    /**
     * @var TokenInterface|null
     *
     * @ORM\OneToOne(targetEntity="Hanaboso\PipesFramework\User\Entity\Token", inversedBy="tmpUser")
     */
    protected $token;

    /**
     * @return string
     */
    public function getType(): string
    {
        return UserTypeEnum::TMP_USER;
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return '';
    }

    /**
     * @param string $pwd
     *
     * @return UserInterface
     */
    public function setPassword(string $pwd): UserInterface
    {
        return $this;
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
     * @return array
     */
    public function toArray(): array
    {
        return [];
    }

}