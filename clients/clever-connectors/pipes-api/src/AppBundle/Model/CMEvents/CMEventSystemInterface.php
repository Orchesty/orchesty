<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 16:00
 */

namespace CleverConnectors\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;

/**
 * Interface CMEventSystemInterface
 *
 * @package CleverConnectors\AppBundle\Model\CMEvents
 */
interface CMEventSystemInterface
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
     * @return RequesterInterface
     */
    public function getCMEventRequester(): RequesterInterface;

}