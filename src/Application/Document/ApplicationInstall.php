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
 * Class ApplicationInstall
 *
 * @package Hanaboso\PipesFramework\Application\Document
 *
 * @ODM\Document
 * @ODM\HasLifecycleCallbacks()
 */
class ApplicationInstall
{

    public const USER = 'user';
    public const NAME = 'name';

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
    private $name;

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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ApplicationInstall
     */
    public function setName(string $name): ApplicationInstall
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @ODM\PreFlush
     * @throws CryptException
     * @throws DateTimeException
     */
    public function preFlush(): void
    {
        $this->encryptedSettings = CryptManager::encrypt($this->settings);
        $this->updated           = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @ODM\PostLoad
     * @throws CryptException
     */
    public function postLoad(): void
    {
        $this->settings = CryptManager::decrypt($this->encryptedSettings);
    }

}
