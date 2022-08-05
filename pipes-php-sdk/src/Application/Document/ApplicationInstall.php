<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Document;

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
 * @package Hanaboso\PipesPhpSdk\Application\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository")
 * @ODM\HasLifecycleCallbacks()
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
    private array $settings = [];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $encryptedSettings = '';

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
     * @return mixed[]
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param mixed[] $settings
     *
     * @return ApplicationInstall
     */
    public function setSettings(array $settings): ApplicationInstall
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @param mixed[] $settings
     *
     * @return ApplicationInstall
     */
    public function addSettings(array $settings): ApplicationInstall
    {
        $this->settings = array_merge($this->settings, $settings);

        return $this;
    }

    /**
     * @return string
     */
    public function getEncryptedSettings(): string
    {
        return $this->encryptedSettings;
    }

    /**
     * @param string $encryptedSettings
     *
     * @return ApplicationInstall
     */
    public function setEncryptedSettings(string $encryptedSettings): ApplicationInstall
    {
        $this->encryptedSettings = $encryptedSettings;

        return $this;
    }

    /**
     * @param string $user
     *
     * @return ApplicationInstall
     */
    public function setUser(string $user): ApplicationInstall
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
    public function setExpires(?DateTime $expires): ApplicationInstall
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
    public function setKey(string $key): ApplicationInstall
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
    public function setNonEncryptedSettings(array $nonEncryptedSettings): ApplicationInstall
    {
        $this->nonEncryptedSettings = $nonEncryptedSettings;

        return $this;
    }

    /**
     * @param mixed[] $nonEncryptedSettings
     *
     * @return ApplicationInstall
     */
    public function addNonEncryptedSettings(array $nonEncryptedSettings): ApplicationInstall
    {
        $this->nonEncryptedSettings = array_merge($this->nonEncryptedSettings, $nonEncryptedSettings);

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $expires = $this->getExpires();

        return [
            'id'                     => $this->getId(),
            ApplicationInstall::USER => $this->getUser(),
            ApplicationInstall::KEY  => $this->getKey(),
            'nonEncryptedSettings'   => $this->getNonEncryptedSettings(),
            'created'                => $this->getCreated()->format(DateTimeUtils::DATE_TIME),
            'updated'                => $this->getUpdated()->format(DateTimeUtils::DATE_TIME),
            'expires'                => $expires ? $expires->format(DateTimeUtils::DATE_TIME) : NULL,
        ];
    }

}
