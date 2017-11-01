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
     * @param string   $actionName
     * @param array    $users
     * @param array    $groups
     * @param int|NULL $number
     */
    public function start(
        string $processId,
        string $actionName,
        array $users = [],
        array $groups = [],
        ?int $number = NULL
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
