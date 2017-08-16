<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 4/3/17
 * Time: 3:00 PM
 */

namespace Hanaboso\PipesFramework\Mailer;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class MailerException
 *
 * @package Hanaboso\PipesFramework\Mailer
 */
class MailerException extends PipesFrameworkException
{

    protected const OFFSET = 700;

    public const MISSING_TEMPLATE_ENGINE = self::OFFSET + 1;

}
