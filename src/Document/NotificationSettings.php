<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Crypt\Exceptions\CryptException;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\UpdatedTrait;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class NotificationSettings
 *
 * @package Hanaboso\NotificationSender\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\NotificationSender\Repository\NotificationSettingsRepository")
 * @ODM\UniqueIndex(name="UK_notification_settings_name", keys={"name"="asc"})
 * @ODM\HasLifecycleCallbacks()
 */
class NotificationSettings
{

    use IdTrait;
    use CreatedTrait;
    use UpdatedTrait;

    public const ID         = 'id';
    public const CREATED    = 'created';
    public const UPDATED    = 'updated';
    public const TYPE       = 'type';
    public const NAME       = 'name';
    public const CLASS_NAME = 'class';
    public const EVENTS     = 'events';
    public const SETTINGS   = 'settings';

    private const EMPTY = '01_N86jVkKpY154CDLSDO92ZLH4PVg3zxZ6ea83UBanK9o=:hUijwPbtwKyeK8Wa9WwWxOuJJ5CDRL2v9CJYYdAg1Fg=:LmP28iFgUwppq42xmve7tI+cnT+WD+sD:A4YIOJjqBHWm3WDTTu67jbHuPb+2Og==';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $class;

    /**
     * @var mixed[]
     *
     * @ODM\Field(type="collection")
     */
    private $events = [];

    /**
     * @var mixed[]
     */
    private $settings = [];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $encryptedSettings;

    /**
     * NotificationSettings constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
        $this->updated = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     *
     * @return NotificationSettings
     */
    public function setClass(string $class): NotificationSettings
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param mixed[] $events
     *
     * @return NotificationSettings
     */
    public function setEvents(array $events): NotificationSettings
    {
        $this->events = array_map(static fn(string $event): string => NotificationEventEnum::isValid($event), $events);

        return $this;
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
     * @return NotificationSettings
     */
    public function setSettings(array $settings): NotificationSettings
    {
        $this->settings = $settings;

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
     * @ODM\PostLoad
     * @throws CryptException
     */
    public function postLoad(): void
    {
        $this->settings = CryptManager::decrypt($this->encryptedSettings ?: self::EMPTY);
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return mixed[]
     */
    public function toArray(string $type, string $name): array
    {
        return [
            self::ID         => $this->id,
            self::CREATED    => $this->created->format(DateTimeUtils::DATE_TIME),
            self::UPDATED    => $this->updated->format(DateTimeUtils::DATE_TIME),
            self::NAME       => $name,
            self::TYPE       => $type,
            self::CLASS_NAME => $this->class,
            self::EVENTS     => $this->events,
            self::SETTINGS   => $this->settings,
        ];
    }

}
