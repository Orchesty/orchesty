<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Database\Traits\Entity\CreatedTrait;

/**
 * Class ApiToken
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 *
 * @ODM\UniqueIndex(name="UniqueKeyIndex", keys={"key" = "desc"})
 * @ODM\Index(name="expireIndex", keys={"expireAt"=1}, options={"expireAfterSeconds"=0})
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Configurator\Repository\ApiTokenRepository")
 */
class ApiToken
{

    use IdTrait;
    use CreatedTrait;

    public const ID        = 'id';
    public const CREATED   = 'created';
    public const USER      = 'user';
    public const KEY       = 'key';
    public const EXPIRE_AT = 'expireAt';
    public const SCOPES    = 'scopes';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $user;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $key;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    private ?string $expireAt = NULL;

    /**
     * @var string[]
     *
     * @ODM\Field(type="collection")
     */
    private array $scopes;

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return ApiToken
     */
    public function setUser(string $user): ApiToken
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return ApiToken
     */
    public function setKey(string $key): ApiToken
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExpireAt(): ?string
    {
        return $this->expireAt;
    }

    /**
     * @param string|null $expireAt
     *
     * @return ApiToken
     */
    public function setExpireAt(?string $expireAt): ApiToken
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param mixed[] $scopes
     *
     * @return ApiToken
     */
    public function setScopes(array $scopes): ApiToken
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::ID        => $this->id,
            self::CREATED   => $this->created ?? NULL,
            self::USER      => $this->user,
            self::KEY       => $this->key,
            self::EXPIRE_AT => $this->expireAt,
            self::SCOPES    => implode(',', $this->scopes),
        ];
    }

}
