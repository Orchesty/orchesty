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
     * @var string
     */
    private $processId;

    /**
     * @var bool
     */
    private $status;

    /**
     * ProcessStatusEvent constructor.
     *
     * @param string $processId
     * @param bool $status
     */
    public function __construct(string $processId, bool $status)
    {
        $this->processId = $processId;
        $this->status    = $status;
    }

    /**
     * @return string
     */
    public function getProcessId(): string
    {
        return $this->processId;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

}