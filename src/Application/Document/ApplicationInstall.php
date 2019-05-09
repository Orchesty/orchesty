<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Crypt\Exceptions\CryptException;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;

/**
 * Class Install
 *
 * @package Hanaboso\PipesFramework\Application\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository")
 *
 * @ODM\HasLifecycleCallbacks()
 */
class ApplicationInstall
{

    public const USER = 'user';
    public const KEY  = 'key';

    use IdTrait;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $created;

    /**
     * @var dateTime;
     *
     * @ODM\Field(type="date")
     */
    private $updated;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $user;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $key;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date", nullable=true)
     */
    private $expires;

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $encryptedSettings;

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
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     *
     * @return ApplicationInstall
     */
    public function setSettings(array $settings): ApplicationInstall
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
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
     * @param DateTime $expires
     *
     * @return ApplicationInstall
     */
    public function setExpires(DateTime $expires): ApplicationInstall
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getExpires(): DateTime
    {
        return $this->expires;
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
     * @ODM\PreFlush
     * @throws CryptException
     */
    public function preFlush(): void
    {
        $this->encryptedSettings = CryptManager::encrypt($this->settings);
    }

    /**
     * @ODM\PreUpdate
     * @throws DateTimeException
     */
    public function preUpdate(): void
    {
        $this->updated = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @ODM\PostLoad
     * @throws CryptException
     */
    public function postLoad(): void
    {
        $this->settings = CryptManager::decrypt($this->encryptedSettings);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'                     => $this->getId(),
            ApplicationInstall::USER => $this->getUser(),
            ApplicationInstall::KEY  => $this->getKey(),
            'expires'                => $this->getExpires(),
            'settings'               => $this->getSettings(),
            'create'                 => $this->getCreated()->format(DateTimeUtils::DATE_TIME),
            'updated'                => $this->getUpdated()->format(DateTimeUtils::DATE_TIME),
        ];
    }

}
