<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\DeletedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\UpdatedTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class ApplicationInstall
 *
 * @package Hanaboso\PipesFramework\Application\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository")
 */
class ApplicationInstall
{

    use IdTrait;
    use CreatedTrait;
    use UpdatedTrait;
    use DeletedTrait;

    public const USER = 'user';
    public const KEY  = 'key';

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
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    private bool $enabled = FALSE;

    /**
     * @var DateTime|null
     *
     * @ODM\Field(type="date", nullable=true)
     */
    private ?DateTime $expires = NULL;

    /**
     * @var mixed[]
     *
     * @ODM\Field(type="hash")
     */
    private array $nonEncryptedSettings = [];

    /**
     * ApplicationInstall constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
        $this->updated = DateTimeUtils::getUtcDateTime();
    }


    /**
     * @param string $user
     *
     * @return ApplicationInstall
     */
    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return DateTime|null
     */
    public function getExpires(): ?DateTime
    {
        return $this->expires;
    }

    /**
     * @param DateTime|null $expires
     *
     * @return ApplicationInstall
     */
    public function setExpires(?DateTime $expires): self
    {
        $this->expires = $expires;

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
     * @return ApplicationInstall
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getNonEncryptedSettings(): array
    {
        return $this->nonEncryptedSettings;
    }

    /**
     * @param mixed[] $nonEncryptedSettings
     *
     * @return ApplicationInstall
     */
    public function setNonEncryptedSettings(array $nonEncryptedSettings): self
    {
        $this->nonEncryptedSettings = $nonEncryptedSettings;

        return $this;
    }

    /**
     * @param mixed[] $nonEncryptedSettings
     *
     * @return ApplicationInstall
     */
    public function addNonEncryptedSettings(array $nonEncryptedSettings): self
    {
        $this->nonEncryptedSettings = array_merge($this->nonEncryptedSettings, $nonEncryptedSettings);

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return ApplicationInstall
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $expires = $this->getExpires();

        return [
            'created'                => $this->getCreated()->format(DateTimeUtils::DATE_TIME),
            'enabled'                => $this->isEnabled(),
            'expires'                => $expires ? $expires->format(DateTimeUtils::DATE_TIME) : NULL,
            'id'                     => $this->getId(),
            'nonEncryptedSettings'   => $this->getNonEncryptedSettings(),
            'updated'                => $this->getUpdated()->format(DateTimeUtils::DATE_TIME),
            self::KEY  => $this->getKey(),
            self::USER => $this->getUser(),
        ];
    }

}
