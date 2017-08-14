<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Traits\IdTrait;

/**
 * Class UserAbstract
 *
 * @package Hanaboso\PipesFramework\User\Document
 *
 */
abstract class UserAbstract
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
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return UserAbstract
     */
    public function setEmail(string $email): UserAbstract
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
     * @param DateTime $created
     *
     * @return UserAbstract
     */
    public function setCreated(DateTime $created): UserAbstract
    {
        $this->created = $created;

        return $this;
    }

}