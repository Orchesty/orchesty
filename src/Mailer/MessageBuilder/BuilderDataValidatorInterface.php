<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:26 PM
 */

namespace Hanaboso\PipesFramework\Mailer\MessageBuilder;

/**
 * Interface BuilderDataValidatorInterface
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageBuilder
 */
interface BuilderDataValidatorInterface
{

    /**
     * @param array $data
     *
     * @return bool
     */
    public function isValid(array $data): bool;

}
