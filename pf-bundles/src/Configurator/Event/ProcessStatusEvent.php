<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ProcessStatusEvent
 *
 * @package Hanaboso\PipesFramework\Configurator\Event
 */
class ProcessStatusEvent extends Event
{

    public const PROCESS_FINISHED = 'finished';

    /**
     * @var mixed
     */
    private $data;

    /**
     * ProcessStatusEvent constructor.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

}