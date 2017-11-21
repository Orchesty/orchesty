<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\DataLayout\Exceptions;

use CleverConnectors\AppBundle\Exceptions\Exception;

/**
 * Class LayoutException
 *
 * @package CleverConnectors\AppBundle\Model\DataLayout\Exceptions
 */
final class LayoutException extends Exception
{

    public const DATA_LAYOUT_ALREADY_EXISTS = 1;

}