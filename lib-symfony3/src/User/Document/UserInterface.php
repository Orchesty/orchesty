<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use DateTime;

/**
 * Interface UserInterface
 *
 * @package Hanaboso\PipesFramework\User\Document
 */
interface UserInterface
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

}