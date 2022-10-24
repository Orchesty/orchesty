<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Common\Events;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class EventEnum
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Common\Events
 */
final class EventEnum extends EnumAbstract
{

    public const PROCESS_SUCCESS  = 'processSuccess';
    public const PROCESS_FAILED   = 'processFailed';
    public const LIMIT_OVERFLOW   = 'limitOverflow';
    public const MESSAGE_IN_TRASH = 'messageInTrash';

}
