<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:26 PM
 */

namespace Hanaboso\PipesFramework\Mailer\MessageHandler;

/**
 * Interface HandlerDataValidatorInterface
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageHandler
 */
interface HandlerDataValidatorInterface
{

    /**
     * @param array $data
     *
     * @return bool
     */
    public function isValid(array $data): bool;

}
