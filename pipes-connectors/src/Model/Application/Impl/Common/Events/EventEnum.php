<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Common\Events;

/**
 * Class EventEnum
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Common\Events
 */
enum EventEnum: string
{

    case PROCESS_SUCCESS  = 'processSuccess';
    case PROCESS_FAILED   = 'processFailed';
    case LIMIT_OVERFLOW   = 'limitOverflow';
    case MESSAGE_IN_TRASH = 'messageInTrash';

}
