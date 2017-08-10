<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 4/3/17
 * Time: 3:00 PM
 */

namespace Hanaboso\PipesFramework\Mailer;

use LogicException;

/**
 * Class MailerException
 *
 * @package Hanaboso\PipesFramework\Mailer
 */
class MailerException extends LogicException
{

    public const MISSING_TEMPLATE_ENGINE = 1;

}
