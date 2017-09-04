<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Hanaboso\PipesFramework\Commons\Traits\Entity\IdTrait;

/**
 * Class UserAbstract
 *
 * @package Hanaboso\PipesFramework\User\Entity
 *
 */
abstract class UserAbstract implements UserInterface
{

    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="date")
     */
    protected $created;

    /**
     * @var Token
     *
     * @ORM\OneToOne(targetEntity="Hanaboso\PipesFramework\User\Entity\User", inversedBy="user")
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