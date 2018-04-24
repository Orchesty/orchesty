<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 13.10.17
 * Time: 13:57
 */

namespace CleverConnectors\AppBundle\Utils;

use Hanaboso\CommonsBundle\Utils\PipesHeaders;

/**
 * Class CMHeaders
 *
 * @package CleverConnectors\AppBundle\Utils
 */
class CMHeaders extends PipesHeaders
{

    public const SYSTEM_KEY    = 'system-key';
    public const GUID          = 'guid';
    public const TOKEN         = 'token';
    public const CM_EVENT_TYPE = 'cm-event-type';

}