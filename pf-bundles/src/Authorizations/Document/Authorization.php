<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 2:01 PM
 */

namespace Hanaboso\PipesFramework\Authorizations\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Traits\IdTrait;

/**
 * Class Authorization
 *
 * @package Hanaboso\PipesFramework\Authorizations\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Authorizations\Repository\AuthorizationRepository")
 */
class Authorization
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $authorizationKey;

    /**
     * @var string[]
     */
    private $token = [];


    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $encrypted = '';

    /**
     * Authorization constructor.
     *
     * @param string $id
     */
    function __construct(string $id)
    {
        $this->authorizationKey = $id;
    }

    /**
     * @return string[]
     */
    public function getToken(): array
    {
        return $this->token;
    }

    /**
     * @param string[] $token
     *
     * @return Authorization
     */
    public function setToken($token): Authorization
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorizationKey(): string
    {
        return $this->authorizationKey;
    }

    /**
     * @param string $authorizationKey
     *
     * @return Authorization
     */
    public function setAuthorizationKey(string $authorizationKey): Authorization
    {
        $this->authorizationKey = $authorizationKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getEncrypted(): string
    {
        return $this->encrypted;
    }

    /**
     * @param string $encrypted
     */
    public function setEncrypted(string $encrypted): void
    {
        $this->encrypted = $encrypted;
    }

}