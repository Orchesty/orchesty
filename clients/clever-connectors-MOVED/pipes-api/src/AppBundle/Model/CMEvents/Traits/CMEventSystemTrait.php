<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 16:11
 */

namespace CleverConnectors\AppBundle\Model\CMEvents\Traits;

use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;

/**
 * Trait CMEventSystemTrait
 *
 * @package CleverConnectors\AppBundle\Model\CMEvents\Traits
 */
trait CMEventSystemTrait
{

    /**
     * @var CMEventObject[]
     */
    protected $cmEvents;

    /**
     * @var CMEventObject[]
     */
    private $indexedEvents = [];

    /**
     * @param CMEventObject $eventObject
     */
    public function addCMEvent(CMEventObject $eventObject): void
    {
        $this->cmEvents[] = $eventObject;
    }

    /**
     * @return array
     */
    public function getCMEvents(): array
    {
        return $this->cmEvents;
    }

    /**
     * @param string $event
     *
     * @return bool
     */
    public function isEventAllowed(string $event): bool
    {
        $this->indexEvents();

        return isset($this->indexedEvents[$event]);
    }

    /**
     * @param string $event
     *
     * @return bool
     */
    public function isEventProcessAllowed(string $event): bool
    {
        $this->indexEvents();

        return isset($this->indexedEvents[$event])
            && !empty($this->indexedEvents[$event]->getUrl());
    }

    /**
     * @param string $event
     *
     * @return CMEventObject
     */
    public function getEventObject(string $event): CMEventObject
    {
        $this->indexEvents();

        return $this->indexedEvents[$event];
    }

    /**
     *
     */
    private function indexEvents(): void
    {
        if (empty($this->indexedEvents)) {
            foreach ($this->cmEvents as $cmEvent) {
                $this->indexedEvents[$cmEvent->getEvent()] = $cmEvent;
            }
        }
    }

}