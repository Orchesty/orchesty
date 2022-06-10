<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\UpdatedTrait;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\Exception\EnumException;

/**
 * Class NotificationSettings
 *
 * @package Hanaboso\NotificationSender\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\NotificationSender\Repository\NotificationSettingsRepository")
 * @ODM\UniqueIndex(name="UK_notification_settings_name", keys={"class"="asc"})
 * @ODM\HasLifecycleCallbacks()
 */
class NotificationSettings
{

    use IdTrait;
    use CreatedTrait;
    use UpdatedTrait;

    public const ID             = 'id';
    public const CREATED        = 'created';
    public const UPDATED        = 'updated';
    public const TYPE           = 'type';
    public const NAME           = 'name';
    public const CLASS_NAME     = 'class';
    public const EVENTS         = 'events';
    public const SETTINGS       = 'settings';
    public const STATUS         = 'status';
    public const STATUS_MESSAGE = 'status_message';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $class = '';

    /**
     * @var mixed[]
     *
     * @ODM\Field(type="collection")
     */
    private array $events = [];

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
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    private bool $status = FALSE;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", nullable=true)
     */
    private ?string $statusMessage = NULL;

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
     * @throws EnumException
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
     * @return string
     */
    public function getEncryptedSettings(): string
    {
        return $this->encryptedSettings;
    }

    /**
     * @param string $encryptedSettings
     *
     * @return NotificationSettings
     */
    public function setEncryptedSettings(string $encryptedSettings): NotificationSettings
    {
        $this->encryptedSettings = $encryptedSettings;

        return $this;
    }

    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     *
     * @return NotificationSettings
     */
    public function setStatus(bool $status): NotificationSettings
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    /**
     * @param string|null $statusMessage
     *
     * @return NotificationSettings
     */
    public function setStatusMessage(?string $statusMessage): NotificationSettings
    {
        $this->statusMessage = $statusMessage;

        return $this;
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
            self::ID             => $this->id,
            self::CREATED        => $this->created->format(DateTimeUtils::DATE_TIME),
            self::UPDATED        => $this->updated->format(DateTimeUtils::DATE_TIME),
            self::NAME           => $name,
            self::TYPE           => $type,
            self::CLASS_NAME     => $this->class,
            self::EVENTS         => $this->events,
            self::SETTINGS       => $this->settings,
            self::STATUS         => $this->status,
            self::STATUS_MESSAGE => $this->statusMessage,
        ];
    }

}
