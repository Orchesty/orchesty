<?php declare(strict_types=1);

namespace CleverCore\Commons\Exceptions;

use Exception;

/**
 * Class RangerException
 *
 * @package CleverCore\Commons\Exceptions
 */
abstract class RangerException extends Exception
{

    protected const OFFSET_COMMONS    = 1000;
    protected const OFFSET_RANGER     = 2000;
    protected const OFFSET_STORE      = 3000;
    protected const OFFSET_TEMPLATING = 4000;
    protected const OFFSET_CAMPAIGN   = 5000;
    protected const OFFSET_CONNECTORS = 6000;
    protected const OFFSET_WORKFLOW   = 7000;

}