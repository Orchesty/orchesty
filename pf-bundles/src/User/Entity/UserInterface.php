<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Entity;

use DateTime;
use Symfony\Component\Security\Core\User\UserInterface as SecurityCoreUserInterface;

/**
 * Interface UserInterface
 *
 * @package Hanaboso\PipesFramework\User\Entity
 */
interface UserInterface extends SecurityCoreUserInterface
{

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getEmail(): string;

    /**
     * @param string $email
     *
     * @return UserInterface
     */
    public function setEmail(string $email): UserInterface;

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param string $pwd
     *
     * @return UserInterface
     */
    public function setPassword(string $pwd): UserInterface;

}