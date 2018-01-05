<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 14:52
 */

namespace CleverConnectors\AppBundle\Model\ProgressCounter;

use CleverConnectors\AppBundle\Enum\ProgressCounterStatusEnum;

/**
 * Interface ProgressCounterInterface
 *
 * @package CleverConnectors\AppBundle\Model\ProgressCounter
 */
interface ProgressCounterInterface
{

    /**
     * @param string   $processId
     * @param string   $eventName
     * @param array    $groups
     * @param int|NULL $number
     * @param array    $metadata
     */
    public function start(
        string $processId,
        string $eventName,
        array $groups = [],
        ?int $number = NULL,
        array $metadata = []
    ): void;

    /**
     * @param string $processId
     * @param int    $total
     */
    public function setTotal(string $processId, int $total): void;

    /**
     * @param string $processId
     */
    public function increment(string $processId): void;

    /**
     * @param string                    $processId
     * @param ProgressCounterStatusEnum $status
     */
    public function setStatus(string $processId, ProgressCounterStatusEnum $status): void;

}
