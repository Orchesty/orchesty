<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Entity;

use DateTime;

/**
 * Interface TokenInterface
 *
 * @package Hanaboso\PipesFramework\User\Entity
 */
interface TokenInterface
{

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime;

    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface;

    /**
     * @param UserInterface $user
     *
     * @return TokenInterface
     */
    public function setUser(UserInterface $user): TokenInterface;

    /**
     * @return UserInterface|TmpUserInterface|null
     */
    public function getTmpUser(): ?UserInterface;

    /**
     * @param UserInterface|null $tmpUser
     *
     * @return TokenInterface
     */
    public function setTmpUser(?UserInterface $tmpUser): TokenInterface;

    /**
     * @return UserInterface|TmpUserInterface
     */
    public function getUserOrTmpUser(): UserInterface;

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getHash(): string;

}