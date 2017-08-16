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
 * Class AuthorizationToken
 *
 * @package Hanaboso\PipesFramework\Authorizations\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Authorizations\Repository\AuthorizationTokenRepository")
 */
class AuthorizationToken
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $connectorKey;

    /**
     * @var string|string[]
     *
     * @ODM\Field(type="string")
     */
    private $data;

    /**
     * @return string|string[]
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string|string[] $data
     *
     * @return AuthorizationToken
     */
    public function setData($data): AuthorizationToken
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getConnectorKey(): string
    {
        return $this->connectorKey;
    }

    /**
     * @param string $connectorKey
     *
     * @return AuthorizationToken
     */
    public function setConnectorKey(string $connectorKey): AuthorizationToken
    {
        $this->connectorKey = $connectorKey;

        return $this;
    }

}