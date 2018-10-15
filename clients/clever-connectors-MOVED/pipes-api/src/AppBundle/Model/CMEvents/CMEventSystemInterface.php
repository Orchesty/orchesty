<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 16:00
 */

namespace CleverConnectors\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;

/**
 * Interface CMEventSystemInterface
 *
 * @package CleverConnectors\AppBundle\Model\CMEvents
 */
interface CMEventSystemInterface extends SystemInterface
{

    /**
     * @return array
     */
    public function getCMEvents(): array;

    /**
     * @param CMEventObject $eventObject
     */
    public function addCMEvent(CMEventObject $eventObject): void;

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|null
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface;

    /**
     * @param string $event
     *
     * @return bool
     */
    public function isEventAllowed(string $event): bool;

    /**
     * @param string $event
     *
     * @return bool
     */
    public function isEventProcessAllowed(string $event): bool;

    /**
     * @param string $event
     *
     * @return CMEventObject
     */
    public function getEventObject(string $event): CMEventObject;

}