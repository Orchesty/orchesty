<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 18:24
 */

namespace CleverConnectors\AppBundle\Model\ProgressCounter\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ProgressCounterEvent
 *
 * @package CleverConnectors\AppBundle\Model\ProgressCounter\Event
 */
class ProgressCounterEvent extends Event
{

    public const ON_PROGRESS_SET_STATUS = 'progress.status.set';

    /**
     * @var string
     */
    protected $processId;

    /**
     * @var string
     */
    protected $status;

    /**
     * ProgressCounterEvent constructor.
     *
     * @param string $processId
     * @param string $status
     */
    public function __construct($processId, string $status)
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
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

}
