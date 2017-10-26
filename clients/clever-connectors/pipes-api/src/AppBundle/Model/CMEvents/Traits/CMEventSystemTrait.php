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
        foreach ($this->cmEvents as $cmEvent) {
            if ($cmEvent->getEvent() == $event) {
                return TRUE;
            }
        }

        return FALSE;
    }

}