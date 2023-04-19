<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

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
     * @var DateTime|null
     *
     * @ODM\Field(type="date")
     */
    private ?DateTime $expireAt = NULL;

    /**
     * @var string[]
     *
     * @ODM\Field(type="collection")
     */
    private array $scopes;

    /**
     * ApiToken constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
    }

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
    public function setUser(string $user): self
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
    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getExpireAt(): ?DateTime
    {
        return $this->expireAt;
    }

    /**
     * @param DateTime|null $expireAt
     *
     * @return ApiToken
     */
    public function setExpireAt(?DateTime $expireAt): self
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
    public function setScopes(array $scopes): self
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
            self::CREATED   => $this->created->format(DateTimeUtils::DATE_TIME_UTC),
            self::EXPIRE_AT => $this->expireAt ? $this->expireAt->format(DateTimeUtils::DATE_TIME_UTC) : NULL,
            self::ID        => $this->id,
            self::KEY       => $this->key,
            self::SCOPES    => implode(',', $this->scopes),
            self::USER      => $this->user,
        ];
    }

}
