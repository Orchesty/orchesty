<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 16:08
 */

namespace CleverConnectors\AppBundle\Model\CMEvents;

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
     * CMEventObject constructor.
     *
     * @param string $field
     * @param string $event
     */
    public function __construct(string $field, string $event)
    {
        $this->field = $field;
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

}