<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 18:24
 */

namespace Hanaboso\PipesFramework\Commons\ProgressCounter\Event;

use Hanaboso\PipesFramework\Commons\Enum\ProgressCounterStatusEnum;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ProgressCounterEvent
 *
 * @package Hanaboso\PipesFramework\Commons\ProgressCounter\Event
 */
class ProgressCounterEvent extends Event
{

    public const ON_PROGRESS_SET_STATUS = 'progress.status.set';

    /**
     * @var string
     */
    protected $processId;

    /**
     * @var ProgressCounterStatusEnum
     */
    protected $status;

    /**
     * ProgressCounterEvent constructor.
     *
     * @param string                    $processId
     * @param ProgressCounterStatusEnum $status
     */
    public function __construct($processId, ProgressCounterStatusEnum $status)
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
     * @return ProgressCounterStatusEnum
     */
    public function getStatus(): ProgressCounterStatusEnum
    {
        return $this->status;
    }

}
