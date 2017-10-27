<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 16:08
 */

namespace CleverConnectors\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;

/**
 * Class CMEventObject
 *
 * @package CleverConnectors\AppBundle\Model\CMEvents
 */
final class CMEventObject
{

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $event;

    /**
     * @var string
     */
    private $url;

    /**
     * CMEventObject constructor.
     *
     * @param string $field
     * @param string $event
     * @param string $url
     *
     * @throws CleverConnectorsException
     */
    public function __construct(string $field, string $event, string $url)
    {
        SystemInstall::checkEvent($event);
        $this->field = $field;
        $this->url   = $url;
        $this->event = $event;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

}