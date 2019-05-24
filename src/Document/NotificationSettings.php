<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Traits\Document\UpdatedTrait;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;

/**
 * Class NotificationSettings
 *
 * @package Hanaboso\NotificationSender\Document
 *
 * @ODM\Document()
 * @ODM\UniqueIndex(name="UK_notification_settings_name", keys={"name"="asc"})
 * @ODM\HasLifecycleCallbacks()
 */
class NotificationSettings
{

    public const ID         = '_id';
    public const TYPE       = 'type';
    public const NAME       = 'name';
    public const CLASS_NAME = 'class';
    public const EVENTS     = 'events';
    public const SETTINGS   = 'settings';

    use IdTrait;
    use CreatedTrait;
    use UpdatedTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $class;

    /**
     * @var array
     *
     * @ODM\Field(type="collection")
     */
    private $events = [];

    /**
     * @var array
     *
     * @ODM\Field(type="hash")
     */
    private $settings = [];

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
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param array $events
     *
     * @return NotificationSettings
     */
    public function setEvents(array $events): NotificationSettings
    {
        $this->events = array_map(function (string $event): string {
            return NotificationEventEnum::isValid($event);
        }, $events);

        return $this;
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
     * @return NotificationSettings
     */
    public function setSettings(array $settings): NotificationSettings
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return array
     */
    public function toArray(string $type, string $name): array
    {
        return [
            'id'       => $this->id,
            'created'  => $this->created->format(DateTimeUtils::DATE_TIME),
            'updated'  => $this->updated->format(DateTimeUtils::DATE_TIME),
            'name'     => $name,
            'type'     => $type,
            'class'    => $this->class,
            'events'   => $this->events,
            'settings' => $this->settings,
        ];
    }

}
